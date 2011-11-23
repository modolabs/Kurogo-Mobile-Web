<?php

Kurogo::includePackage('Maps');

class MapWebModule extends WebModule {

    protected $id = 'map';

    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $dataModel = null;
    protected $numGroups;
    protected $feeds;
    protected $featureIndex;
    protected $mapDevice = null;
    
    private function getDataForGroup($group) {
        return isset($this->feedGroups[$group]) ? $this->feedGroups[$group] : null;
    }
    
    public function getFeedGroups() {
        return $this->getModuleSections('feedgroups');
    }

    private function getCategory() {
        $category = $this->getArg('category', null);
        return $category;
    }

    private function getDrillDownPath() {
        $path = $this->getArg('path', array());
        if ($path !== array()) {
            $path = explode(MAP_CATEGORY_DELIMITER, $path);
        }
        // remove empty strings from beginning of array
        while (count($path) && !strlen($path[0])) {
            array_shift($path);
        }
        return $path;
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
            $category = $this->getCategory();
            // if no feed group and category are specified, load whole list
            foreach ($this->feedGroups as $groupID => $groupData) {
                $configName = "feeds-$groupID";
                $groupData = array();
                foreach ($this->getModuleSections($configName) as $id => $feedData) {
                    $feedId = mapIdForFeedData($feedData);
                    $groupData[$feedId] = $feedData;
                    if ($category == $feedId) {
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

    private function getFeedData()
    {
        if (!$this->feeds) {
            $this->feeds = $this->loadFeedData();
        }
        return $this->feeds;
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
        $urlArgs = $this->args;
        $addBreadcrumb = $options && isset($options['addBreadcrumb']) && $options['addBreadcrumb'];
        if (isset($options['external']) && $options['external']) {
            $urlArgs['external'] = true;
        }

        $result = array(
            'title'    => $mapItem->getTitle(),
            'subtitle' => $mapItem->getSubtitle(),
            );

        if ($mapItem instanceof Placemark) {
            if ($mapItem instanceof BasePlacemark && ($url = $mapItem->getURL())) {
                // if url was set via setURL -- only applies to campus placemarks on worldmap
                $result['url'] = $url;

            } else {
                $urlArgs = array_merge($urlArgs, shortArrayFromMapFeature($mapItem));
                // for map driven UI we want placemarks to show up on the full screen map
                if ($this->isMapDrivenUI() && $this->page != 'index') {
                    $result['url'] = $this->buildURL('index', $urlArgs);
                } else {
                    $result['url'] = $this->buildBreadcrumbURL('detail', $urlArgs, $addBreadcrumb);
                }
            }

            if (($distance = $mapItem->getField('distance')) && $this->getOptionalModuleVar('SHOW_DISTANCES', true)) {
                $result['subtitle'] = $this->displayTextFromMeters($distance);
            }

        } else {
            // for folder objects, getId returns the subcategory ID
            $drilldownPath = array_merge($this->getDrillDownPath(), array($mapItem->getId()));
            $external = isset($urlArgs['external']) ? $urlArgs['external'] : false;
            $result['url'] = $this->categoryURL($this->getCategory(), $drilldownPath, $external);
        }

        return $result;
    }
    
    ///////////// url builders

    // $category can be a string or array which specifies the drilldown path 
    // if null, user will be redirected to index
    private function categoryURL($category=null, $path=array(), $addBreadcrumb=true) {
        $args = array();
        if ($category !== NULL) {
            if (is_array($category)) {
                $category = implode(MAP_CATEGORY_DELIMITER, $category);
            }
            $args['category'] = $category;
            $args['path'] = implode(MAP_CATEGORY_DELIMITER, $path);
            if ($this->feedGroup) {
                $args['group'] = $this->feedGroup;
            }
        }
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }
    
    private function groupURL($group, $addBreadcrumb=false) {
        $args = $this->args;
        $args['group'] = $group;
        //$args['action'] = ($group == '') ? 'remove' : 'add';
        return $this->buildBreadcrumbURL('index', $args, $addBreadcrumb);
    }
  
    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex']) || isset($params['lat'], $params['lon'])) {
            if ($this->isMapDrivenUI()) {
                if ($this->feedGroup) {
                    $params['group'] = $this->feedGroup;
                }
                return $this->buildURL('index', $params);
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

    // assumes feeds are loaded
    private function getDataModel($category=null)
    {
        // re-instantiate DataModel if the category has changed
        // since categories are usually representations of feeds.
        if ($category !== $this->getCategory()) {
            $this->dataModel = null;
        }

        if ($this->dataModel === null) {
            $feedData = $this->getCurrentFeed($category);
            if (isset($feedData['CONTROLLER_CLASS'])) { // legacy
                $modelClass = $feedData['CONTROLLER_CLASS'];
            }
            elseif (isset($feedData['MODEL_CLASS'])) {
                $modelClass = $feedData['MODEL_CLASS'];
            }
            else {
                $modelClass = 'MapDataModel';
            }

            try {
                $this->dataModel = MapDataModel::factory($modelClass, $feedData);
            } catch (KurogoConfigurationException $e) {
                $this->dataModel = DataController::factory($modelClass, $feedData);
            }
        }

        $drilldownPath = $this->getDrillDownPath();
        if ($drilldownPath) {
            $this->dataModel->addDisplayFilter('category', $drilldownPath);
        }
        return $this->dataModel;
    }

    private function getCurrentFeed($category=null) {
        $this->getFeedData();
        if ($category === null) {
            $category = $this->getCategory();
        }
        if ($category) {
            if (isset($this->feeds[$category])) {
                return $this->feeds[$category];
            }
        }
        Kurogo::log(LOG_WARNING,"Warning: unable to find feed data for category $category",'maps');
        return null;
    }

    private function getMergedConfigData() {
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
        $feedData = $this->getCurrentFeed();
        if ($feedData) {
            foreach ($feedData as $key => $value) {
                $configData[$key] = $value;
            }
        }

        return $configData;
    }

    private function getMapDevice()
    {
        if (!$this->mapDevice) {
            $this->mapDevice = new MapDevice($this->pagetype, $this->platform);
        }
        return $this->mapDevice;
    }

    protected function isMapDrivenUI()
    {
        list($class, $static) = MapImageController::basemapClassForDevice(
            $this->getMapDevice(),
            $this->getMergedConfigData());
        return !$static;
    }

    private function getImageController()
    {
        return MapImageController::factory(
            $this->getMergedConfigData(),
            $this->getMapDevice());
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
    
    private function assignCategories() {
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
                'url'      => $this->categoryURL($id),
                );
        }
        
        $this->assign('categories', $categories);
        return $categories;
    }

    protected function assignClearLink()
    {
        $clearLink = array(array(
            'title' => $this->getLocalizedString('ALL_MAP_GROUPS'),
            'url' => $this->groupURL(''),
            ));
        $this->assign('clearLink', $clearLink);
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex'])) {
            $index = $params['featureindex'];
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

    protected function displayTextFromMeters($meters)
    {
        $result = null;
        $system = $this->getOptionalModuleVar('DISTANCE_MEASUREMENT_UNITS', 'Metric');
        switch ($system) {
            case 'Imperial':
                $miles = $meters * MILES_PER_METER;
                if ($miles < 0.1) {
                    $feet = $meters * FEET_PER_METER;
                    $result = $this->getLocalizedString(
                        'DISTANCE_IN_FEET',
                         number_format($feet, 0));

                } elseif ($miles < 15) {
                    $result = $this->getLocalizedString(
                        'DISTANCE_IN_MILES',
                         number_format($miles, 1));
                } else {
                    $result = $this->getLocalizedString(
                        'DISTANCE_IN_MILES',
                         number_format($miles, 0));
                }
                break;
            case 'Metric':
            default:
                if ($meters < 100) {
                    $result = $this->getLocalizedString(
                        'DISTANCE_IN_METERS',
                         number_format($meters, 0));
                } elseif ($meters < 15000) {
                    $result = $this->getLocalizedString(
                        'DISTANCE_IN_KILOMETERS',
                         number_format($meters / 1000, 1));
                } else {
                    $result = $this->getLocalizedString(
                        'DISTANCE_IN_KILOMETERS',
                         number_format($meters / 1000, 0));
                }
                
                break;
        }
        return $result;
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

                $searchResults = $mapSearch->searchByProximity($center, $tolerance, $maxItems, $this->getDataModel());
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

    protected function bookmarkIDForPlacemark($placemark) {
        if ($placemark) {
            $category = current($placemark->getCategoryIds());
            $cookieParams = array(
                'category' => $category,
                'featureindex' => $placemark->getId(),
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

        $this->addJQuery();
        $this->featureIndex = $this->getArg('featureindex', null);

        switch ($this->page) {

            case 'index':

                if (($this->feedGroup !== null || $this->getArg('worldmap')) && $this->isMapDrivenUI()) {
                    $this->setTemplatePage('fullscreen');
                    if ($this->getArg('worldmap')) {
                        $this->feedGroup = null;
                        $this->assign('browseURL', $this->buildURL('index'));
                    }
                    $this->initializeDynamicMap();

                } else {
                    $this->setupCampusPage();
                }

                break;
            
            case 'campus':
                $this->setTemplatePage('index');
                $this->setupCampusPage();

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
            
            case 'search':

                $searchTerms = $this->getArg('filter');
                if ($searchTerms) {
                    // TODO: redirect if there is one result
                    $args = array_merge($this->args, array('addBreadcrumb' => true));

                    // still need a way to show the Google logo if we use their search
                    $searchResults = $this->searchItems($searchTerms, null, $args);
                    $places = array();
                    foreach ($searchResults as $place) {
                        $places[] = $this->linkForItem($place);
                    }
                    $this->assign('searchTerms', $searchTerms);
                    $this->assign('places',      $places);
                  
                } else {
                    $this->redirectTo('index');
                }
                break;
            
            case 'category':

                $category = $this->getCategory();
                if ($category) {
                    // populate drop-down list at the bottom
                    $categories = $this->assignCategories();
                    // build the drill-down list
                    $dataModel = $this->getDataModel();
                    $listItems = $dataModel->getListItems();

                    if (count($listItems) == 1) {
                        // redirect to a category's children if it only has one item
                        $args = $this->args;
                        if (current($listItems) instanceof Placemark) {
                            $args['featureindex'] = current($listItems)->getId();
                            $this->redirectTo('detail', $args, true);
                        } else { // assume MapFolder
                            $path = $this->getDrillDownPath();
                            $path[] = current($listItems)->getId();
                            $args['path'] = implode(MAP_CATEGORY_DELIMITER, $path);
                            $this->redirectTo('category', $args, false);
                        }
                    }

                    $places = array();
                    foreach ($listItems as $listItem) {
                        $places[] = $this->linkForItem($listItem);
                    }
                    $this->assign('title',  $dataModel->getTitle());
                    $this->assign('places', $places);          
                    
                    if ($this->numGroups > 1 && count($categories) == 1) {
                        $this->assignClearLink();
                    }
                  
                } else {
                    $this->redirectTo('index');
                }
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

        // check if any placemarks were passed from another page
        if ($this->featureIndex !== null) {
            $dataModel = $this->getDataModel();
            $dataModel->selectPlacemark($this->featureIndex);
            return $dataModel->getSelectedPlacemarks();
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

        // TODO: add ways to show all bookmarks in a campus,
        // all placemarks within a category

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
            $this->assignCategories();

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

            foreach ($this->feedGroups as $id => $groupData) {
                $categories[] = array(
                    'id' => $id,
                    'title' => $groupData['title'],
                    'url' => $this->groupURL($id),
                    'listclass' => $id, // stupid way to sneak the id into the dom
                    );
            }
            $this->assign('campuses', $categories);
            $this->assign('categories', $categories);

            $this->addOnLoad('sortGroupsByDistance();');
        }
    }

    protected function initializeDynamicMap()
    {
        if ($this->feedGroup) {
            $urlArgs = array('group' => $this->feedGroup);
            $browseURL = $this->buildBreadcrumbURL('campus', $urlArgs, true);
            $this->assign('browseURL', $browseURL); // browse button
        }

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
}
