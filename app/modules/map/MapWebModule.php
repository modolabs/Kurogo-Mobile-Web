<?php

Kurogo::includePackage('Maps');

define('MAP_GROUP_COOKIE', 'mapgroup');

class MapWebModule extends WebModule {

    protected $id = 'map';
    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $numGroups = 1;
    protected $feeds;
    protected $featureIndex;
    
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

    public function linkForItem(KurogoObject $placemark, $options=null)
    {
        $addBreadcrumb = $options && isset($options['addBreadcrumb']) && $options['addBreadcrumb'];
        $urlArgs = shortArrayFromMapFeature($placemark);
        if (isset($options['external']) && $options['external']) {
            $urlArgs['external'] = true;
        }
        $result = array(
            'title' => $placemark->getTitle(),
            'subtitle' => $placemark->getSubtitle(),
            'url' => $this->buildBreadcrumbURL('detail', $urlArgs, $addBreadcrumb),
            );

        if (($distance = $placemark->getField('distance')) && $this->getOptionalModuleVar('SHOW_DISTANCES', true)) {
            $result['subtitle'] = $this->displayTextFromMeters($distance);
        }

        return $result;
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
        }
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }
    
    private function groupURL($group, $addBreadcrumb=false) {
        $args = $this->args;
        $args['group'] = $group;
        $args['action'] = ($group == '') ? 'remove' : 'add';
        return $this->buildBreadcrumbURL('index', $args, $addBreadcrumb);
    }

    /*
    public function detailURLForLatLon(Array $coordinate, $object=null) {

    }

    public function detailURLForAddress($address) {

    }
    */

    private function detailURL($name, $category=null, $addBreadcrumb=true) {
        $args = $this->args;
        $args['featureindex'] = $name;
        return $this->buildBreadcrumbURL('detail', $args, $addBreadcrumb);
    }
  
    private function detailUrlForPan($direction, $imgController) {
        $args = $this->args;
        $center = $imgController->getCenterForPanning($direction);
        $args['center'] = $center['lat'] .','. $center['lon'];
        return $this->buildBreadcrumbURL('detail', $args, false);
    }

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

    // depends on feeds being loaded
    private function getDataController($category=null) {
        $feedData = $this->getCurrentFeed($category);
        if ($feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
        }
        if (!isset($controller)) {
            $controller = MapDataController::defaultDataController();
        }
        return $controller;
    }

    private function getCurrentFeed($category=null) {
        $this->getFeedData();
        if ($category === null) {
            $category = $this->getCategory();
        }
        if ($category) {
            if (isset($this->feeds[$category])) {
                return $this->feeds[$category];
            } else {
                Kurogo::log(LOG_WARNING,"Warning: unable to find feed data for category $category",'maps');
            }
        }
        return null;
    }

    private function getImageController()
    {
        // if base map class is specified in individual feeds, override base
        // map class in feed group.  this takes care of compatibility with v1.2
        // map feed config.
        $configData = $this->getCurrentFeed();
        if (!isset($configData['STATIC_MAP_CLASS']) && !isset($configData['JS_MAP_CLASS'])) {
            if ($this->feedGroup === null) {
                Kurogo::log(LOG_WARNING,"Warning: feed group not set when initializing image controller, using first group",'maps');
                $this->feedGroup = key($this->feedGroups);
            }
            $configData = $this->getDataForGroup($this->feedGroup);
        }
        $mapDevice = new MapDevice($this->pagetype, $this->platform);

        return MapImageController::factory($configData, $mapDevice);
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

    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex']) || isset($params['lat'], $params['lon'])) {
            return $this->buildBreadcrumbURL('detail', $params, true);
        } else {
            return '#';
        }
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex'])) {
            $index = $params['featureindex'];
            $category = $params['category'];
            $dataController = $this->getDataController($category);
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

    /////// UI functions
    
    protected function addJavascriptStaticMap() {
        // Let Webkit figure out what the window size is and then hide the address bar
        // and resize the map
        $this->addOnLoad('setTimeout(function () { window.scrollTo(0, 1); updateMapDimensions(); }, 1000);');
        $this->addOnOrientationChange('updateMapDimensions();');
    }

    protected function addJavascriptDynamicMap() {
        $this->addInlineJavascriptFooter("\n hide('loadingimage');\n");
        $this->addOnOrientationChange('updateContainerDimensions()');
    }

    protected function initializeMapElements($mapElement, $imgController)
    {
        if ($imgController->isStatic()) {
            $this->assign('imageUrl', $imgController->getImageURL());

            $this->assign('scrollNorth', $this->detailUrlForPan('n', $imgController));
            $this->assign('scrollEast', $this->detailUrlForPan('e', $imgController));
            $this->assign('scrollSouth', $this->detailUrlForPan('s', $imgController));
            $this->assign('scrollWest', $this->detailUrlForPan('w', $imgController));

            $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $imgController));
            $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $imgController));

            $this->assign('imageWidth',  $imgController->getImageWidth());
            $this->assign('imageHeight', $imgController->getImageHeight());

            if (($this->pagetype == 'compliant' && $this->platform != 'bbplus') || $this->pagetype == 'tablet') {
                $apiURL = FULL_URL_BASE.API_URL_PREFIX."/{$this->configModule}/staticImageURL";
                $js = <<<JS
                    mapWidth = {$imgController->getImageWidth()};
                    mapHeight = {$imgController->getImageHeight()};
                    staticMapOptions = {$imgController->getJavascriptControlOptions()};
                    apiURL = "{$apiURL}";
JS;

                $this->addInlineJavascript($js);
                $this->addOnLoad('addStaticMapControls();');
            }

        } else {
            $imgController->setMapElement($mapElement);
            foreach ($imgController->getIncludeScripts() as $includeScript) {
                $this->addExternalJavascript($includeScript);
            }
            $this->addInlineJavascript($imgController->getHeaderScript());
            $this->addInlineJavascriptFooter($imgController->getFooterScript());
        }

        $showUserLocation = $this->getOptionalModuleVar('MAP_SHOWS_USER_LOCATION', false);
        if ($showUserLocation) {
            $this->addInlineJavascript("\nshowUserLocation = true;\n");
        }
    }
    
    private function initializeMap(MapDataController $dataController, $fullscreen=FALSE)
    {
        $placemarks = $dataController->getSelectedPlacemarks();

        $imgController = $this->getImageController();
        foreach ($placemarks as $placemark) {
            $imgController->addPlacemark($placemark);
        }
        
        // override point for current zoom level
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } else {
            $zoomLevel = $dataController->getDefaultZoomLevel();
        }
        
        // override point for where map should be centered
        if (isset($this->args['center'])) {
            $center = filterLatLon($this->getArg('center'));
        } elseif (isset($this->args['lat'], $this->args['lon'])) {
            $center = array('lat' => $this->getArg('lat'), 'lon' => $this->getArg('lon'));
        }

        if (isset($center)) {
            $imgController->setCenter($center);
        }
        if (isset($zoomLevel) && $zoomLevel !== null) {
            $imgController->setZoomLevel($zoomLevel);
        }

        if (!$fullscreen) {
            $this->assign('fullscreenURL', $this->buildBreadcrumbURL('fullscreen', $this->args, false));
        
            if (!$imgController->isStatic()) {
                $this->addInlineJavascriptFooter("\n hideMapTabChildren();\n");
            }
            
        } else {
            $this->assign('detailURL', $this->buildBreadcrumbURL('detail', $this->args, false));

        }
        
        $this->assign('fullscreen', $fullscreen);
        $this->assign('isStatic', $imgController->isStatic());
        
        $this->initializeMapElements('mapimage', $imgController);

        // call the function that updates the image size        
        if ($imgController->isStatic()) {
            $this->addJavascriptStaticMap();
        } else {
            $this->addJavascriptDynamicMap();
        }
    }

    // return true on success, false on failure
    protected function generateTabForKey($tabKey, $feature, $dataController, &$tabJavascripts) {
        switch ($tabKey) {
            case 'map':
            {
                $this->initializeMap($dataController);
                return true;
            }
            case 'nearby':
            {
                if ($feature) {
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

                // check for settings in feedgroup config
                $configData = $this->getDataForGroup($this->feedGroup);
                if ($configData) {
                    if (isset($configData['NEARBY_THRESHOLD'])) {
                        $tolerance = $configData['NEARBY_THRESHOLD'];
                    }
                    if (isset($configData['NEARBY_ITEMS'])) {
                        $maxItems = $configData['NEARBY_ITEMS'];
                    }
                }

                // check for override settings in feeds
                $configData = $this->getCurrentFeed();
                if (isset($configData['NEARBY_THRESHOLD'])) {
                    $tolerance = $configData['NEARBY_THRESHOLD'];
                }
                if (isset($configData['NEARBY_ITEMS'])) {
                    $maxItems = $configData['NEARBY_ITEMS'];
                }

                $searchResults = $mapSearch->searchByProximity($center, $tolerance, $maxItems, $dataController);
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
                if (!$feature) {
                    return false;
                }

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
                if ($feature) {
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

    protected function initializeForPage() {
        $this->featureIndex = $this->getArg('featureindex', null);

        switch ($this->page) {
            case 'help':
                break;

            case 'index':

                if ($action = $this->getArg('action', false)) {
                    if ($this->feedGroup && $action == 'add') {
                        // TODO have config for different types of cookie expiration times
                        $expireTime = time() + 897298;
                        setcookie(MAP_GROUP_COOKIE, $this->feedGroup, $expireTime, COOKIE_PATH);
                    } else if ($action == 'remove') {
                        $expireTime = time() - 4096;
                        setcookie(MAP_GROUP_COOKIE, '', $expireTime, COOKIE_PATH);
                    }
                }

                if ($this->numGroups == 0) {
                    $categories = array(array(
                        'title' => $this->getLocalizedString('NO_MAPS_FOUND'),
                        ));
                    $this->assign('categories', $categories);

                } else if ($this->feedGroup === null && $this->numGroups > 1) {
                    // show the list of groups
                    foreach ($this->feedGroups as $id => $groupData) {
                        $categories[] = array(
                            'title' => $groupData['title'],
                            'url' => $this->groupURL($id),
                            'listclass' => $id, // stupid way to sneak the id into the dom
                            );
                    }

                    // TODO there should ba a cleaner way to do this
                    $apiURL = FULL_URL_BASE.API_URL_PREFIX."/{$this->configModule}";
                    $this->addInlineJavascript("\napiURL = '$apiURL';\n");

                    $groupAlias = $this->getLocalizedString('MAP_GROUP_ALIAS');
                    $this->assign('browseHint', $this->getLocalizedString('SELECT_A_MAP_GROUP', $groupAlias));
                    $this->assign('categories', $categories);

                    $this->addOnLoad('sortGroupsByDistance();');
                    
                } else {
                    $groupData = $this->getDataForGroup($this->feedGroup);
                    $this->assign('browseBy', $groupData['title']);
                    if ($this->numGroups > 1) {
                        $groupAlias = $this->getLocalizedString('MAP_GROUP_ALIAS_PLURAL');
                        $clearLink = array(array(
                            'title' => "All $groupAlias",
                            'url' => $this->groupURL(''),
                            ));
                        $this->assign('clearLink', $clearLink);
                    }
                    
                    $categories = $this->assignCategories();
                    
                    /*
                    if (count($categories)==1) {
                        $category = current($categories);
                        $this->redirectTo('category', array('category'=>$category['id']));
                    }
                    */
                }

                $this->assign('placeholder', $this->getLocalizedString('MAP_SEARCH_PLACEHOLDER'));
                $this->assign('tip', $this->getLocalizedString('MAP_SEARCH_TIP'));

                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->generateBookmarkLink();
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
            
            case 'search':

                $searchTerms = $this->getArg('filter');
                if ($searchTerms) {
                    $this->feedGroup = null;

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
                    $this->assignCategories();
                    // build the drill-down list
                    $dataController = $this->getDataController();
                    $dataController->addDisplayFilter('category', $this->getDrillDownPath());
                    $listItems = $dataController->getListItems();

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
                        if ($listItem instanceof Placemark) {
                            $url = $this->detailURL($listItem->getId(), $category);
                        } else {
                            // for folder objects, getIndex returns the subcategory ID
                            $drilldownPath = array_merge($this->getDrillDownPath(), array($listItem->getId()));
                            $url = $this->categoryURL($category, $drilldownPath, false);
                        }
                        $places[] = array(
                            'title'    => $listItem->getTitle(),
                            'subtitle' => $listItem->getSubtitle(),
                            'url'      => $url,
                            );
                    }
                    $this->assign('title',  $dataController->getTitle());
                    $this->assign('places', $places);          
                    
                    if ($this->numGroups > 1) {
                        $categories = $this->assignCategories();
                        if (count($categories)==1) {
                            $groupAlias = $this->getLocalizedString('MAP_GROUP_ALIAS_PLURAL');
                            $clearLink = array(array(
                                'title' => "All $groupAlias",
                                'url' => $this->groupURL(''),
                                ));
                            $this->assign('clearLink', $clearLink);
                        }
                    }
                    
                  
                } else {
                      $this->redirectTo('index');
                }
                break;
          
            case 'detail':
                $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');        
                $tabKeys = array();
                $tabJavascripts = array();
                $title = $this->getArg('title');

                $dataController = $this->getDataController();
                $drilldownPath = $this->getDrillDownPath();
                if ($drilldownPath) {
                    $dataController->addDisplayFilter('category', $drilldownPath);
                }
                if ($this->featureIndex !== null) {
                    $feature = $dataController->selectPlacemark($this->featureIndex);

                } elseif (isset($this->args['lat'], $this->args['lon'])) {
                    $lat = $this->args['lat'];
                    $lon = $this->args['lon'];
                    $feature = new BasePlacemark(
                        new MapBasePoint(array(
                            'lat' => $lat,
                            'lon' => $lon,
                            )));
                    if (!$title) {
                        $title = "$lat,$lon";
                    }
                    // hacky
                    $dataController->setSelectedPlacemarks(array($feature));
                }

                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    if (isset($this->args['featureindex'])) { // this is a place from a feed
                        $cookieParams = array(
                            'category' => $this->getCategory(),
                            'featureindex' => $this->featureIndex,
                            );
                        $cookieID = http_build_query($cookieParams);
                        $this->generateBookmarkOptions($cookieID);
    
                    } elseif (isset($this->args['lat'], $this->args['lon'])) {
                        $cookieParams = array(
                            'lat' => $this->args['lat'],
                            'lon' => $this->args['lon'],
                            );
                        if ($feature) {
                            $cookieParams['title'] = $feature->getTitle();
                        }
                        $cookieID = http_build_query($cookieParams);
                        $this->generateBookmarkOptions($cookieID);

                    }
                }
                
                if ($feature) {
                    if (!$title) {
                        $title = $feature->getTitle();
                    }
                    // prevent infinite loop in smarty_modifier_replace
                    // TODO figure out why smarty gets in an infinite loop
                    $address = str_replace("\n", " ", $feature->getSubtitle());
                } else {
                    // TODO put something reasonable here
                    $title = '';
                    $address = $this->getArg('address');
                }
                $this->assign('name', $title);
                $this->assign('address', $address);
                $possibleTabs = $detailConfig['tabs']['tabkeys'];
                foreach ($possibleTabs as $tabKey) {
                    if ($this->generateTabForKey($tabKey, $feature, $dataController, $tabJavascripts)) {
                        $tabKeys[] = $tabKey;
                    }
                }
        
                $this->assign('tabKeys', $tabKeys);
                $this->enableTabs($tabKeys, null, $tabJavascripts);
                break;
                
            case 'fullscreen':
                $dataController = $this->getDataController();
                $dataController->selectPlacemark($this->featureIndex);
                $this->initializeMap($dataController, true);
                break;
        }
    }
}
