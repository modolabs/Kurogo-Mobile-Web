<?php

Kurogo::includePackage('Maps');

class MapWebModule extends WebModule {

    protected $id = 'map';

    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $dataModel = null;
    protected $numGroups;
    protected $feeds;
    protected $placemarkId;
    protected $mapDevice = null;

    ////// inherited and conventional module functions
    
    public function getFeedGroups() {
        return $this->getModuleSections('feedgroups');
    }
     
    // overrides function in Module.php
    protected function loadFeedData() {
        $data = array();
        $feedConfigFile = NULL;

        if ($this->feedGroup !== NULL) {
            $configName = "feeds-{$this->feedGroup}";
            foreach ($this->getModuleSections($configName) as $id => $feedData) {
                $feedId = mapIdForFeedData($feedData);
                $data[$feedId] = $feedData;
            }

        } else {
            $requestedFeedId = $this->getArg('feed', null);
            // if no feed group and category are specified, load whole list
            foreach ($this->feedGroups as $groupID => $groupSettings) {
                $configName = "feeds-$groupID";
                $groupData = array();
                foreach ($this->getModuleSections($configName) as $id => $feedData) {
                    $feedId = mapIdForFeedData($feedData);
                    $groupData[$feedId] = $feedData;
                    if ($requestedFeedId == $feedId) {
                        $this->feedGroup = $groupID;
                    }
                }

                if ($this->feedGroup !== null) {
                    $data = $groupData;
                    break;
                } else {
                    $data = array_merge($data, $groupData);
                }
            }
        }

        return $data;
    }

    protected function getModuleAdminSections() {
        $sections = parent::getModuleAdminSections();
        
        foreach ($this->getFeedGroups() as $feedgroup=>$data) {
            $sections['feeds-'.$feedgroup] = array(
                'title'=>$data['title'],
                'type'=>'module hidden'
            );
        }
        
        return $sections;
    }
    
    protected function getModuleAdminConfig() {
        $configData = parent::getModuleAdminConfig();
        
        foreach ($this->getFeedGroups() as $feedgroup=>$data) {
            $feedData = $configData['feed'];
            $feedData['title'] = $data['title'];
            $feedData['config'] = 'feeds-' . $feedgroup;
            $feedData['configMode'] = ConfigFile::OPTION_CREATE_EMPTY;
            $configData['feeds-'.$feedgroup] = $feedData;
        }
        unset($configData['feed']);
        
        return $configData;
    }

    public function searchItems($searchTerms, $limit=null, $options=null)
    {
        $addBreadcrumb = isset($options['addBreadcrumb']) && $options['addBreadcrumb'];
        $mapSearch = $this->getSearchClass($options);
        $searchResults = array_values($mapSearch->searchCampusMap($searchTerms));
        if ($limit) {
            return array_slice($searchResults, 0, $limit);
        }
        return $searchResults;
    }

    public function linkForValue($value, Module $callingModule, KurogoObject $otherValue=null)
    {
        $external = !$callingModule instanceof MapWebModule;

        $latLon = filterLatLon($value);
        if ($latLon !== false) {
            $page = 'detail';
            $urlParams = $latLon;
        } else {
            $page = 'search';
            $urlParams = array(
                'filter'   => $value,
                'external' => $external, // determines which search engine to use
                );
        }

        return array(
            'title' => $value,
            'class' => 'map', // css class to show map icon in table rows
            'url'   => $this->buildBreadcrumbURL($page, $urlParams, false),
        );
    }

    public function linkForItem(KurogoObject $mapItem, $options=null)
    {
        $result = array(
            'title'    => $mapItem->getTitle(),
            'subtitle' => $mapItem->getSubtitle(),
            );

        if ($mapItem instanceof Placemark) {
            if ($mapItem instanceof BasePlacemark && ($url = $mapItem->getURL())) {
                // if url was set via setURL -- only applies to campus placemarks on worldmap
                $result['url'] = $url;

            } else {
                $urlArgs = array_merge($this->args, shortArrayFromMapFeature($mapItem));
                if (!isset($urlArgs['group'])) {
                    $urlArgs['group'] = $this->feedGroup;
                }
                $addBreadcrumb = $options && isset($options['addBreadcrumb']) && $options['addBreadcrumb'];
                $result['url'] = $this->buildBreadcrumbURL('detail', $urlArgs, $addBreadcrumb);
                // for map driven UI we want placemarks to show up on the full screen map
                $category = key($mapItem->getCategoryIds());
                if ($this->isMapDrivenUI($urlArgs['category'])) {
                    $mapPage = ($this->numGroups > 1) ? 'campus' : 'index';
                    if ($this->page != $mapPage) {
                        $result['url'] = $this->buildURL($mapPage, $urlArgs);
                    }
                }
            }

            if (($distance = $mapItem->getField('distance')) && $this->getOptionalModuleVar('SHOW_DISTANCES', true)) {
                $result['subtitle'] = $this->displayTextFromMeters($distance);
            }

        } else {
            $external = $options && isset($options['external']) && $options['external'];
            $category = implode(MAP_CATEGORY_DELIMITER, $mapItem->getIdStack());
            $result['url'] = $this->categoryURL($category, null, null, $external);
        }

        return $result;
    }

    ////// private data retrieval
    
    private function getDataForGroup($group) {
        return isset($this->feedGroups[$group]) ? $this->feedGroups[$group] : null;
    }

    private function getFeedData()
    {
        if (!$this->feeds) {
            $this->feeds = $this->loadFeedData();
        }
        return $this->feeds;
    }

    // assumes feeds are loaded
    private function getDataModel($feedId=null)
    {
        // re-instantiate DataModel if a different feed is requested.
        if ($this->dataModel && $feedId !== $this->dataModel->getFeedId()) {
            $this->dataModel = null;
        }

        if ($this->dataModel === null) {
            $feedData = $this->getCurrentFeed($feedId);
            $this->dataModel = mapModelFromFeedData($feedData);
        }

        return $this->dataModel;
    }

    private function getCurrentFeed($feedId=null) {
        $this->getFeedData();
        if ($feedId === null || $feedId === '') {
            $feedId = $this->getArg('feed');
        }
        if ($feedId) {
            if (isset($this->feeds[$feedId])) {
                return $this->feeds[$feedId];
            }
        }
        Kurogo::log(LOG_WARNING,"Warning: unable to find feed data for feed $feedId",'maps');
        return null;
    }

    private function getMergedConfigData($feedId=null) {
        if ($this->getArg('worldmap')) {
            return array();
        }

        // allow individual feeds to override values in the feed group
        if ($this->feedGroup === null) {
            // putting this to see if/when this happens
            throw new Exception("feed group not set");

            Kurogo::log(LOG_WARNING,"Warning: feed group not set when initializing image controller, using first group",'maps');
            $this->feedGroup = key($this->feedGroups);
        }

        $configData = $this->getDataForGroup($this->feedGroup);

        // allow individual feeds to override group value
        $feedData = $this->getCurrentFeed($feedId);
        if ($feedData) {
            foreach ($feedData as $key => $value) {
                $configData[$key] = $value;
            }
        }
        return $configData;
    }

    protected function getSearchClass($options=array()) {
        if (isset($options['external']) && $options['external']) {
            $searchConfigName = 'MAP_EXTERNAL_SEARCH_CLASS';
            $searchConfigDefault = 'GoogleMapSearch';
        } else { // includes federatedSearch
            $searchConfigName = 'MAP_SEARCH_CLASS';
            $searchConfigDefault = 'MapSearch';
        }

        $mapSearchClass = $this->getOptionalModuleVar($searchConfigName, $searchConfigDefault);
        $mapSearch = new $mapSearchClass($this->getFeedData());
        $this->assign('poweredByGoogle', $mapSearch instanceof GoogleMapSearch && $mapSearch->isPlaces());
        return $mapSearch;
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['pid'])) {
            $index = $params['pid'];
            $category = $params['category'];
            $dataController = $this->getDataModel($category);
            $feature = $dataController->selectPlacemark($index);
            return array($feature->getTitle(), $dataController->getTitle());
        
        } else if (isset($params['title'])) {
            $result = array($params['title']);
            if (isset($params['address'])) {
                $result[] = $params['address'];
            }
            return $result;

        } else {
            return array($aBookmark);
        }
    }

    protected function bookmarkIDForPlacemark($placemark) {
        if ($placemark) {
            $category = current($placemark->getCategoryIds());
            $cookieParams = array(
                'category' => $category,
                'pid' => $placemark->getId(),
                );
        } elseif (isset($this->args['lat'], $this->args['lon'])) {
            $cookieParams = array(
                'lat' => $this->args['lat'],
                'lon' => $this->args['lon'],
                );
        }
        $title = $this->getArg('title');
        if ($title) {
            $cookieParams['title'] = $title;
        }
        return http_build_query($cookieParams);
    }
    
    ///////////// url builders

    private function groupURL($group, $addBreadcrumb=false) {
        $args = $this->args;
        $args['group'] = $group;
        if (isset($args['worldmap'])) {
            unset($args['worldmap']);
        }
        $mapPage = ($this->numGroups > 1) ? 'campus' : 'index';
        return $this->buildBreadcrumbURL($mapPage, $args, $addBreadcrumb);
    }
  
    private function feedURL($feed, $group=null, $addBreadcrumb=true) {
        if (!$group) {
            $group = $this->feedGroup;
        }
        $args = array(
            'group' => $group,
            'feed' => $feed,
            );
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }

    private function categoryURL($category, $feed=null, $group=null, $addBreadcrumb=false) {
        if (!$group) {
            $group = $this->feedGroup;
        }
        if (!$feed) {
            $feed = $this->getArg('feed');
        }
        $args = array(
            'group' => $group,
            'feed' => $feed,
            'category' => $category,
            );
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }
    
    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['pid']) || isset($params['lat'], $params['lon'])) {
            if ($this->isMapDrivenUI()) {
                if ($this->feedGroup) {
                    $params['group'] = $this->feedGroup;
                }
                $mapPage = ($this->numGroups > 1) ? 'campus' : 'index';
                return $this->buildURL($mapPage, $params);
            }
            return $this->buildBreadcrumbURL('detail', $params, true);
        } else {
            return '#';
        }
    }

    // static maps only
    private function detailUrlForPan($direction, $imgController) {
        $args = $this->args;
        $center = $imgController->getCenterForPanning($direction);
        $args['center'] = $center['lat'] .','. $center['lon'];
        return $this->buildBreadcrumbURL('detail', $args, false);
    }

    // static maps only
    private function detailUrlForZoom($direction, $imgController) {
        $args = $this->args;
        $args['zoom'] = $imgController->getLevelForZooming($direction);
        return $this->buildBreadcrumbURL('detail', $args, false);
    }

    ////// basemap retrieval

    private function getMapDevice()
    {
        if (!$this->mapDevice) {
            $this->mapDevice = new MapDevice($this->pagetype, $this->platform);
        }
        return $this->mapDevice;
    }

    protected function isMapDrivenUI($category=null)
    {
        list($class, $static) = MapImageController::basemapClassForDevice(
            $this->getMapDevice(),
            $this->getMergedConfigData($category));
        return !$static;
    }

    private function getImageController()
    {
        return MapImageController::factory(
            $this->getMergedConfigData(),
            $this->getMapDevice());
    }

    ///// template control

    private function assignCampuses() {
        $campusData = array();
        if ($this->numGroups > 1) {
            foreach ($this->feedGroups as $id => $groupData) {
                $data = array(
                    'id' => $id,
                    'title' => $groupData['title'],
                    'url' => $this->groupURL($id),
                    'listclass' => $id, // stupid way to sneak the id into the dom
                    );
                if ($this->feedGroup === $id) {
                    $data['selected'] = true;
                }
                $campusData[] = $data;
            }
            $this->assign('campuses', $campusData);
        }
        return $campusData;
    }
    
    private function assignFeeds() {
        $categories = array();
        foreach ($this->getFeedData() as $id => $feed) {
            if (isset($feed['HIDDEN']) && $feed['HIDDEN']) {
                continue;
            }
            $subtitle = isset($feed['SUBTITLE']) ? $feed['SUBTITLE'] : null;
            $categories[] = array(
                'id'       => $id,
                'title'    => $feed['TITLE'],
                'subtitle' => $subtitle,
                'url'      => $this->feedURL($id),
                );
        }
        
        $this->assign('categories', $categories);
        return $categories;
    }

    private function assignSearchResults($searchTerms) {
        $args = array_merge($this->args, array('addBreadcrumb' => true));
        // still need a way to show the Google logo if we use their search
        $searchResults = $this->searchItems($searchTerms, null, $args);
        $places = array();
        foreach ($searchResults as $place) {
            $places[] = $this->linkForItem($place);
        }
        $this->assign('places', $places);
        return $places;
    }

    protected function assignClearLink()
    {
        $clearLink = array(array(
            'title' => $this->getLocalizedString('ALL_MAP_GROUPS'),
            'url' => $this->groupURL(''),
            ));
        $this->assign('clearLink', $clearLink);
    }

    protected function configureUserLocation() {
        // extra javascript
        $showUserLocation = $this->getOptionalModuleVar('MAP_SHOWS_USER_LOCATION', false);
        if ($showUserLocation) {
            $this->addInlineJavascript("\nshowUserLocation = true;\n");
        }
    }

    // return true on success, false on failure
    protected function generateTabForKey($tabKey, $features, &$tabJavascripts) {
        switch ($tabKey) {
            case 'map':
            {
                if ($this->isMapDrivenUI()) {
                    return false;
                } else {
                    $this->initializeStaticMap();
                    return true;
                }
            }
            case 'nearby':
            {
                if (count($features) == 1) {
                    $feature = end($features);
                    $geometry = $feature->getGeometry();
                    $center = $geometry->getCenterCoordinate();
                    $currentId = $feature->getId();
                    $currentTitle = $feature->getTitle();
                } elseif (isset($this->args['lat'], $this->args['lon'])) {
                    $center = array(
                        'lat' => $this->args['lat'],
                        'lon' => $this->args['lon']);
                } else {
                    return false;
                }

                $mapSearch = $this->getSearchClass($this->args);

                // defaults values for proximity search
                $tolerance = 1000;
                $maxItems = 0;

                // feed settings override group settings
                $groupData = $this->getDataForGroup($this->feedGroup);
                $feedData = $this->getCurrentFeed();
                if (isset($feedData['NEARBY_THRESHOLD'])) {
                    $tolerance = $feedData['NEARBY_THRESHOLD'];
                } elseif ($groupData && isset($groupData['NEARBY_THRESHOLD'])) {
                    $tolerance = $groupData['NEARBY_THRESHOLD'];
                }
                if (isset($feedData['NEARBY_ITEMS'])) {
                    $maxItems = $feedData['NEARBY_ITEMS'];
                } elseif ($groupData && isset($groupData['NEARBY_ITEMS'])) {
                    $maxItems = $groupData['NEARBY_ITEMS'];
                }

                //$searchResults = $mapSearch->searchByProximity($center, $tolerance, $maxItems, $this->getDataModel());
                $searchResults = $mapSearch->searchByProximity($center, $tolerance, $maxItems);
                $places = array();
                if ($searchResults) {
                    foreach ($searchResults as $result) {
                        if ($result->getId() !== $currentId || $result->getTitle() !== $currentTitle) {
                            $aPlace = $this->linkForItem($result);
                            $places[] = $aPlace;
                        }
                    }
                    $this->assign('nearbyResults', $places);
                }
                return count($places) > 0;
            }
            case 'info':
            {
                if (count($features) != 1) {
                    return false;
                }
                $feature = end($features);

                // handle embedded photo
                $photoURL = $feature->getField('PhotoURL'); // embedded photo url
                if (isset($photoURL) && $photoURL && $photoURL != 'Null') {
                    $tabJavascripts[$tabKey] = "loadImage(photoURL,'photo');";
                    $this->assign('photoURL', $photoURL);
                    $this->addInlineJavascript("var photoURL = '{$photoURL}';");
                }

                $fields = $feature->getFields();
                if (count($fields) == 1) {
                    $details = current(array_values($fields));
                    $displayDetailsAsList = false;

                } else {
                    $details = array();

                    $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');
                    if (isset($detailConfig['details'], $detailConfig['details']['suppress'])) {
                        $suppress = $detailConfig['details']['suppress'];
                    }

                    foreach ($fields as $name => $value) {
                        if (!isset($suppress) || !in_array($name, $suppress)) {
                            $aDetail = array('label' => $name, 'title' => $value);
                            if (isValidURL($value)) {
                                $aDetail['url'] = $value;
                                $aDetail['class'] = 'external';
                            }
                            $details[] = $aDetail;
                        }
                    }
                    $displayDetailsAsList = true;
                }

                $this->assign('displayDetailsAsList', $displayDetailsAsList);
                $this->assign('details', $details);
                return is_array($details) ? count($details) > 0 : strlen(trim($details));
            }
            case 'links':
            {
                $externalLinks = array();
                if (count($features) == 1) {
                    $feature = end($features);
                    $geometry = $feature->getGeometry();
                    $center = $geometry->getCenterCoordinate();
                } elseif (isset($this->args['lat'], $this->args['lon'])) {
                    $center = array(
                        'lat' => $this->args['lat'],
                        'lon' => $this->args['lon']);
                } else {
                    return false;
                }

                $centerText = $center['lat'].','.$center['lon'];

                $externalLinks[] = array(
                    'title' => $this->getLocalizedString('VIEW_IN_GOOGLE_MAPS'),
                    'url'   => 'http://maps.google.com?ll='.$centerText,
                    'class' => 'external',
                    );
                
                $externalLinks[] = array(
                    'title' => $this->getLocalizedString('GET_DIRECTIONS_FROM_GOOGLE'),
                    'url'   => 'http://maps.google.com?daddr='.$centerText,
                    'urlID' => 'directionsLink',
                    'class' => 'external',
                    );

                $tabJavascripts[$tabKey] = "addDirectionsLink();";
                
                $this->assign('externalLinks', $externalLinks);
                return count($externalLinks) > 0;
            }
            default:
                break;
        }
        
        return false;
    }

    protected function initialize() {
        // this is in the wrong place
        $this->feedGroup = $this->getArg('group', NULL);

        $this->feedGroups = $this->getFeedGroups();
        $this->numGroups = count($this->feedGroups);
        
        if ($this->numGroups === 1) {
            $this->feedGroup = key($this->feedGroups);
        }

        // clear out invalid feed group argument
        if ($this->feedGroup !== NULL && $this->getDataForGroup($this->feedGroup) === NULL) {
            $this->feedGroup = NULL;
        }
    }

    protected function initializeForPage() {

        $this->placemarkId = $this->getArg('pid', null);

        switch ($this->page) {

            case 'index': // no breadcrumbs
            case 'campus': // same as index but with breadcrumb
                $searchTerms = $this->getArg('filter');
                if ($searchTerms) {
                    $this->assign('searchTerms', $searchTerms);
                }
                if ($this->feedGroup) {
                    $this->assign('group', $this->feedGroup);
                }
                $topPage = ($this->numGroups > 1) ? 'campus' : 'index';

                // set up list view if
                if ($this->feedGroup === null // multiple campuses, none selected
                    && !$this->getArg('worldmap') // user did not explictly request map view
                    || $this->getArg('listview') // user did explicitly request list view
                    || !$this->isMapDrivenUI()
                ) {
                    $this->setTemplatePage('index');
                    $this->setupCampusPage();
                    if ($searchTerms) {
                        // user hit the "browse" button with a query string
                        $this->setTemplatePage('browse');
                        $this->assignSearchResults($searchTerms);
                        $urlParams = array('filter' => $searchTerms, 'group' => $this->feedGroup);
                        $this->assign('mapURL', $this->buildURL($topPage, $urlParams));
                        $this->enableTabs(array('search', 'browse'), null, null);
                        $this->addOnLoad('addClass(document.body, "fullscreen")');
                    }
                } else {
                    // set up fullscreen map
                    $this->setTemplatePage('fullscreen');
                    $browseArgs = array('listview' => true);
                    if ($this->getArg('worldmap')) {
                        $this->feedGroup = null;
                    }
                    if ($this->feedGroup) {
                        $browseArgs['group'] = $this->feedGroup;
                    }
                    $this->assign('browseURL', $this->buildBreadcrumbURL($topPage, $browseArgs, false));
                    $this->assignCampuses();
                    $this->initializeDynamicMap();
                }

                break;
            
            case 'bookmarks':
                if (!$this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->redirectTo('index', array());
                }

                $places = array();
                foreach ($this->getBookmarks() as $aBookmark) {
                    if ($aBookmark) { // prevent counting empty string
                        $titles = $this->getTitleForBookmark($aBookmark);
                        $subtitle = count($titles) > 1 ? $titles[1] : null;

                        // TODO split up bookmarks by category
                        $places[] = array(
                            'title' => $titles[0],
                            'subtitle' => $subtitle,
                            'url' => $this->detailURLForBookmark($aBookmark),
                            );
                    }
                }
                $this->assign('places', $places);
                break;
            
            case 'bookmarkmap':
                break;
            
            case 'search':

                $searchTerms = $this->getArg('filter');
                if ($searchTerms) {
                    $this->assign('searchTerms', $searchTerms);
                    $places = $this->assignSearchResults($searchTerms);
                    // TODO: redirect if there is only one result
                  
                } else {
                    $this->redirectTo('index');
                }
                break;
            
            case 'category':

                $feedId = $this->getArg('feed');
                $dataModel = $this->getDataModel($feedId);
                $category = $this->getArg('category', null);
                if ($category !== null) {
                    $dataModel->findCategory($category);
                }
                $title = $dataModel->getTitle();
                $listItems = $dataModel->categories();
                while (count($listItems) == 1) {
                    $categoryId = end($listItems)->getId();
                    $dataModel->findCategory($categoryId);
                    $listItems = $dataModel->categories();
                }
                // TODO: use different nav list types to distinguish placemarks vs. subcategories
                if (!$listItems) {
                    $listItems = $dataModel->placemarks();
                    if (count($listItems) == 1) {
                        $link = $this->linkForItem(current($listItems));
                        $this->redirectTo($link['url']);
                    } else if ($this->getArg('mapview') && $this->isMapDrivenUI()) {
                        $this->setTemplatePage('fullscreen');
                        $this->initializeDynamicMap();
                        break;
                    }
                }

                $places = array();
                foreach ($listItems as $listItem) {
                    $places[] = $this->linkForItem($listItem);
                }
                $this->assign('title',  $title);
                $this->assign('places', $places);

                if ($this->numGroups > 1) {
                    $this->assignClearLink();
                }

                // link to "view all on map"
                $mapArgs = $this->args;
                $mapArgs['mapview'] = true;
                $mapURL = $this->buildBreadcrumbURL($this->page, $mapArgs, false);
                $this->assign('mapURL', $mapURL);

                break;
          
            case 'detail':
                $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');        
                $tabKeys = array();
                $tabJavascripts = array();
                $features = $this->getSelectedPlacemarks();

                $title = '';
                if (count($features) == 1) {
                    $feature = end($features);
                    $title = $feature->getTitle();
                    $address = str_replace("\n", " ", $feature->getSubtitle());
                    if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                        $this->generateBookmarkOptions($this->bookmarkIDForPlacemark($feature));
                    }
                    $link = $this->linkForItem($feature);
                    $this->assign('mapURL', $link['url']);

                } else {
                    if ($this->getArg('worldmap')) {
                        $title = $this->getLocalizedString('ALL_MAP_GROUPS');
                    }
                    $address = $this->getArg('address');
                }

                if ($this->getArg('title')) {
                    $title = $this->getArg('title');
                }

                $this->assign('name', $title);
                $this->assign('address', $address);

                $possibleTabs = $detailConfig['tabs']['tabkeys'];
                foreach ($possibleTabs as $tabKey) {
                    if ($this->generateTabForKey($tabKey, $features, $tabJavascripts)) {
                        $tabKeys[] = $tabKey;
                    }
                }
                $this->assign('tabKeys', $tabKeys);
                $this->enableTabs($tabKeys, null, $tabJavascripts);

                break;
        }
    }

    protected function getSelectedPlacemarks()
    {
        // all campuses
        if ($this->getArg('worldmap')) {
            $placemarks = array();
            foreach ($this->feedGroups as $id => $groupData) {
                $point = filterLatLon($groupData['center']);
                $placemark = new BasePlacemark(
                    new MapBasePoint(array(
                        'lat' => $point['lat'],
                        'lon' => $point['lon'],
                        )));
                $placemark->setTitle($groupData['title']);
                $placemark->setURL($this->groupURL($id));
                $placemarks[] = $placemark;
            }
            return $placemarks;
        }

        // if anything was already selected by something else
        $feedId = $this->getArg('feed');
        if ($feedId) {
            $dataModel = $this->getDataModel($feedId);
            $category = $this->getArg('category', null);
            if ($category !== null) {
                $dataModel->findCategory($category);
            }
            if ($this->placemarkId !== null) {
                $dataModel->setPlacemarkId($this->placemarkId);
            }
            $placemarks = $dataModel->placemarks();
            if ($placemarks) {
                return $placemarks;
            }
        }

        if (($searchTerms = $this->getArg('filter'))) {
            return $this->searchItems($searchTerms, null, $this->args);
        }

        // make the map display arbitrary locations that aren't in any feeds
        if (isset($this->args['lat'], $this->args['lon'])) {
            $lat = $this->args['lat'];
            $lon = $this->args['lon'];
            $title = $this->getArg('title');
            if (!$title) {
                $title = "$lat,$lon";
            }
            $feature = new BasePlacemark(
                new MapBasePoint(array(
                    'lat' => $lat,
                    'lon' => $lon,
                    )));
            $feature->setTitle($title);
            return array($feature);
        }

        // TODO: add ways to show all bookmarks in a campus

        return array();
    }

    protected function setupCampusPage()
    {
        if ($this->feedGroup !== null) {
            $groupData = $this->getDataForGroup($this->feedGroup);
            $this->assign('browseHint', $groupData['title']);
            if ($this->numGroups > 1) {
                $this->assignClearLink();
            }
            $this->assignFeeds();

        } elseif ($this->numGroups == 0) {
            $categories = array(array(
                'title' => $this->getLocalizedString('NO_MAPS_FOUND'),
                ));
            $this->assign('categories', $categories);

        } else if ($this->feedGroup === null) {
            // bookmarks and view all section
            $this->generateBookmarkLink();
            $worldmapPage = $this->getMapDevice()->pageSupportsDynamicMap() ? 'index' : 'detail';
            $worldmapLink = array(array(
                'title' => $this->getLocalizedString('VIEW_ALL_GROUPS_ON_MAP'),
                'url' => $this->buildURL($worldmapPage, array('worldmap' => true)),
                ));
            $this->assign('worldmapLink', $worldmapLink);

            // feedgroups section
            $groupAlias = $this->getLocalizedString('MAP_GROUP_ALIAS');
            $this->assign('browseHint', $this->getLocalizedString('SELECT_A_MAP_GROUP', $groupAlias));
            $campuses = $this->assignCampuses();
            $this->assign('categories', $campuses);

            $this->addOnLoad('sortGroupsByDistance();');
        }
    }

    protected function initializeDynamicMap()
    {
        // set up base map
        $baseMap = $this->getImageController();
        $baseMap->setWebModule($this);

        // add data
        foreach ($this->getSelectedPlacemarks() as $aPlacemark) {
            $baseMap->addPlacemark($aPlacemark);
        }

        // code for embedding base map
        $baseMap->setMapElement('mapimage');
        $baseMap->prepareForOutput();
        foreach ($baseMap->getIncludeScripts() as $includeScript) {
            $this->addExternalJavascript($includeScript);
        }
        $this->addInlineJavascript($baseMap->getHeaderScript());
        $this->addInlineJavascriptFooter($baseMap->getFooterScript());

        $this->configureUserLocation();
        $this->addOnLoad('addClass(document.body, "fullscreen")');
        $this->addOnOrientationChange('updateContainerDimensions()');
    }

    protected function initializeStaticMap()
    {
        $baseMap = $this->getImageController();
        foreach ($this->getSelectedPlacemarks() as $placemark) {
            $baseMap->addPlacemark($placemark);
        }
        $baseMap->prepareForOutput();
        
        // override point for current zoom level
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } elseif (!$this->getArg('worldmap')) {
            $zoomLevel = $this->getDataModel()->getDefaultZoomLevel();
        }
        
        // override point for where map should be centered
        if (isset($this->args['center'])) {
            $center = filterLatLon($this->getArg('center'));
        } elseif (isset($this->args['lat'], $this->args['lon'])) {
            $center = array('lat' => $this->getArg('lat'), 'lon' => $this->getArg('lon'));
        }

        if (isset($center)) {
            $baseMap->setCenter($center);
        }
        if (isset($zoomLevel) && $zoomLevel !== null) {
            $baseMap->setZoomLevel($zoomLevel);
        }
        
        $this->assign('imageUrl', $baseMap->getImageURL());

        $this->assign('scrollNorth', $this->detailUrlForPan('n', $baseMap));
        $this->assign('scrollEast', $this->detailUrlForPan('e', $baseMap));
        $this->assign('scrollSouth', $this->detailUrlForPan('s', $baseMap));
        $this->assign('scrollWest', $this->detailUrlForPan('w', $baseMap));

        // this may not be needed for devices that get the ajax options
        $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $baseMap));
        $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $baseMap));

        $this->assign('imageWidth',  $baseMap->getImageWidth());
        $this->assign('imageHeight', $baseMap->getImageHeight());

        // ajax options for static maps
        // devices like bbplus will load a new page for each zoom/scroll
        if ($this->getMapDevice()->pageSupportsDynamicMap()) {
            $js = <<<JS
                mapWidth = {$imgController->getImageWidth()};
                mapHeight = {$imgController->getImageHeight()};
                staticMapOptions = {$imgController->getJavascriptControlOptions()};
JS;

            $this->addInlineJavascript($js);
            $this->addOnLoad('addStaticMapControls();');
        }

        // javascript for all static maps
        $this->addOnLoad('setTimeout(function () { window.scrollTo(0, 1); updateMapDimensions(); }, 1000);');
        $this->addOnOrientationChange('updateMapDimensions();');
    }

    /////// utilities

    protected function displayTextFromMeters($meters)
    {
        $result = null;
        $system = $this->getOptionalModuleVar('DISTANCE_MEASUREMENT_UNITS', 'Metric');
        switch ($system) {
            case 'Imperial':
                $miles = $meters * MILES_PER_METER;
                if ($miles < 0.1) {
                    $feet = $meters * FEET_PER_METER;
                    $result = $this->getLocalizedString('DISTANCE_IN_FEET', number_format($feet, 0));
                } elseif ($miles < 15) {
                    $result = $this->getLocalizedString('DISTANCE_IN_MILES', number_format($miles, 1));
                } else {
                    $result = $this->getLocalizedString('DISTANCE_IN_MILES', number_format($miles, 0));
                }
                break;
            case 'Metric':
            default:
                if ($meters < 100) {
                    $result = $this->getLocalizedString('DISTANCE_IN_METERS', number_format($meters, 0));
                } elseif ($meters < 15000) {
                    $result = $this->getLocalizedString('DISTANCE_IN_KILOMETERS', number_format($meters / 1000, 1));
                } else {
                    $result = $this->getLocalizedString('DISTANCE_IN_KILOMETERS', number_format($meters / 1000, 0));
                }
                break;
        }
        return $result;
    }
}
