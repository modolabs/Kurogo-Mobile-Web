<?php

Kurogo::includePackage('Maps');

define('MAP_GROUP_COOKIE', 'mapgroup');

class MapWebModule extends WebModule {

    protected $id = 'map';
    protected $bookmarkLinkTitle = 'Bookmarked Locations';
    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $numGroups = 1;
    protected $feeds;
    protected $featureIndex;
    
    private function getDataForGroup($group) {
        if (!$this->feedGroups) {
             $this->feedGroups = $this->getFeedGroups();
        }
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
            if ($this->numGroups === 1) {
                $this->feedGroup = key($this->feedGroups);
            }
        }


        if ($this->numGroups === 0) {
            $data = $this->getModuleSections('feeds');

        } elseif ($this->feedGroup !== NULL) {
            $configName = "feeds-{$this->feedGroup}";
            foreach ($this->getModuleSections($configName) as $id => $feedData) {
                $feedId = mapIdForFeedData($feedData);
                $data[$feedId] = $feedData;
            }
        } else {
            foreach ($this->feedGroups as $groupID => $groupData) {
                $configName = "feeds-$groupID";
                foreach ($this->getModuleSections($configName) as $id => $feedData) {
                    $feedId = mapIdForFeedData($feedData);
                    $data[$feedId] = $feedData;
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
        $result = array(
            'title' => $placemark->getTitle(),
            'subtitle' => $placemark->getSubtitle(),
            'url' => $this->buildBreadcrumbURL('detail', $urlArgs, $addBreadcrumb),
            );
        return $result;
    }

    protected function initialize() {
        // this is in the wrong place
        $this->feedGroup = $this->getArg('group', NULL);
        /* don't save feed group anymore because it has some flaws
        if ($this->feedGroup === NULL) {
            if (isset($_COOKIE[MAP_GROUP_COOKIE])) {
                $this->feedGroup = $_COOKIE[MAP_GROUP_COOKIE];
            }
        }
        */

        $this->feedGroups = $this->getFeedGroups();
        $this->numGroups = count($this->feedGroups);

        // clear out invalid feed group argument
        if ($this->feedGroup !== NULL && $this->getDataForGroup($this->feedGroup) === NULL) {
            $this->feedGroup = NULL;
        }
    }

    // TODO this is an example implementation based on Harvard.
    protected function photoFileForPlacemark(Placemark $placemark)
    {
        $photoFile = null;
        $photoServer = $this->getOptionalModuleVar('MAP_PHOTO_SERVER');
        if ($photoServer) {
            $photoFile = $feature->getField('Photo');
            if ($photoFile == 'Null') {
                $photoFile = null;
            }
        }
        return $photoFile;
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

    public function searchItems($searchTerms, $limit=null, $options)
    {
        $addBreadcrumb = isset($options['addBreadcrumb']) && $options['addBreadcrumb'];
        $mapSearch = $this->getSearchClass($options);
        $searchResults = array_values($mapSearch->searchCampusMap($searchTerms));
        $maxCount = count($searchResults);
        if ($limit && $limit < $maxCount) {
            $maxCount = $limit;
        }
        $results = array();
        for ($i = 0; $i < $maxCount; $i++) {
            $urlParams = shortArrayFromMapFeature($searchResults[$i]);
            $external = $this->getArg('external', null);
            if ($external) {
                $urlParams['external'] = $external;
            }
            $result = array(
                'title' => $searchResults[$i]->getTitle(),
                'url'   => $this->buildBreadcrumbURL('detail', $urlParams, $addBreadcrumb),
                );
            $results[] = $result;
        }
    
        return $results;
    }

    private function getDataController() {
        $category = $this->getCategory();
        if ($category) {
            if (!$this->feeds) {
                $this->feeds = $this->loadFeedData();
            }

            if (isset($this->feeds[$category])) {
                $feedData = $this->feeds[$category];
                $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);

            } else {
                error_log("Warning: unable to find feed data for category $category -- loading default controller");
            }
        }

        if (!isset($controller)) {
            $controller = MapDataController::defaultDataController();
        }

        return $controller;
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
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();
        $mapSearch = new $mapSearchClass($this->feeds);
        $this->assign('poweredByGoogle', $mapSearch instanceof GoogleMapSearch && $mapSearch->isPlaces());
        return $mapSearch;
    }
    
    private function assignCategories() {
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        $categories = array();
        foreach ($this->feeds as $id => $feed) {
            if (isset($feed['HIDDEN']) && $feed['HIDDEN']) continue;
            $subtitle = isset($feed['SUBTITLE']) ? $feed['SUBTITLE'] : null;
            $categories[] = array(
                'id'=>$id,
                'title' => $feed['TITLE'],
                'subtitle' => $subtitle,
                'url' => $this->categoryURL($id),
                );
        }
        
        $this->assign('categories', $categories);
        return $categories;
    }

    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex']) || isset($params['lat'], $params['lon'])) {
            return $this->buildBreadcrumbURL('detail', $params, true);
        } else if (isset($params['group'])) {
            return $this->groupURL($params['group']);
        } else {
            return '#';
        }
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex'])) {
            $index = $params['featureindex'];
            $categoryPath = explode(MAP_CATEGORY_DELIMITER, $params['category']);
            //$dataController = $this->getDataController($categoryPath, $listItemPath);
            $dataController = $this->getDataController();
            $feature = $dataController->selectFeature($index);
            //$feature = $dataController->getFeature($index, $listItemPath);
            return array($feature->getTitle(), $dataController->getTitle());
        
        } else if (isset($params['group'])) {
            $groupData = $this->getDataForGroup($params['group']);
            return array($groupData['title']);

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
    
    private function bookmarkType($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['group']))
            return 'group';
        return 'place';
    }

    /////// UI functions
    
    protected function addJavascriptFullscreenStaticMap() {
        // Let Webkit figure out what the window size is and then hide the address bar
        // and resize the map
        $this->addOnLoad('setTimeout(function () { window.scrollTo(0, 1); updateMapDimensions(); }, 1000);');
        $this->addOnOrientationChange('updateMapDimensions();');
    }

    protected function addJavascriptFullscreenDynamicMap() {
        $this->addInlineJavascriptFooter("\n hide('loadingimage');\n");
        $this->addOnOrientationChange('updateContainerDimensions()');
    }

    protected function initializeMapElements($mapElement, $imgController, $imageWidth, $imageHeight) {
        $imgController->setImageWidth($imageWidth);
        $imgController->setImageHeight($imageHeight);
            
        if ($imgController->isStatic()) {
            if ($this->pagetype == 'basic' || $this->pagetype == 'touch') {
                $imgController->setImageFormat('gif');
            }

            $this->assign('imageUrl', $imgController->getImageURL());

            $this->assign('scrollNorth', $this->detailUrlForPan('n', $imgController));
            $this->assign('scrollEast', $this->detailUrlForPan('e', $imgController));
            $this->assign('scrollSouth', $this->detailUrlForPan('s', $imgController));
            $this->assign('scrollWest', $this->detailUrlForPan('w', $imgController));

            $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $imgController));
            $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $imgController));

            $this->assign('imageWidth',  $imageWidth);
            $this->assign('imageHeight', $imageHeight);

            if (($this->pagetype == 'compliant' && $this->platform != 'bbplus') || $this->pagetype == 'tablet') {
                $apiURL = FULL_URL_BASE.API_URL_PREFIX."/{$this->configModule}/staticImageURL";
                $js = <<<JS
                    mapWidth = {$imageWidth};
                    mapHeight = {$imageHeight};
                    staticMapOptions = {$imgController->getJavascriptControlOptions()};
                    apiURL = "{$apiURL}";
JS;

                $this->addInlineJavascript($js);
                $this->addOnLoad('addStaticMapControls();');
            }

        } else {
            $imgController->setImageWidth($imageWidth);
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
    
    private function initializeMap(MapDataController $dataController, $fullscreen=FALSE) {
        
        $MapDevice = new MapDevice($this->pagetype, $this->platform);

        $imgController = $dataController->getMapImageController($MapDevice);

        // override point for where annotation should be drawn
        if (isset($this->args['lat'], $this->args['lon'])) {
            $customPlacemark = new MapBasePoint(
                $this->args['lat'], $this->args['lon']);
        }
        
        // override point for current zoom level
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } else {
            $zoomLevel = $dataController->getDefaultZoomLevel();
        }
        
        // override point for where map should be centered
        if (isset($this->args['center'])) {
            $latlon = explode(",", $this->args['center']);
            $center = array('lat' => $latlon[0], 'lon' => $latlon[1]);
        } elseif (isset($customPlacemark)) {
            $center = $customPlacemark->getCenterCoordinate();
        }

        if (isset($center)) {
            $imgController->setCenter($center);
        }
        $imgController->setZoomLevel($zoomLevel);

        if (!$fullscreen) {
            $this->assign('fullscreenURL', $this->buildBreadcrumbURL('fullscreen', $this->args, false));
        
            if ($imgController->isStatic()) {
                list($imageWidth, $imageHeight) = $MapDevice->staticMapImageDimensions();

            } else {
                list($imageWidth, $imageHeight) = $MapDevice->dynamicMapImageDimensions();
                $this->addInlineJavascriptFooter("\n hideMapTabChildren();\n");
            }
            
        } else {
            $this->assign('detailURL', $this->buildBreadcrumbURL('detail', $this->args, false));
            if ($imgController->isStatic()) {
                list($imageWidth, $imageHeight) = $MapDevice->staticMapImageDimensions();

            } else {
                list($imageWidth, $imageHeight) = $MapDevice->fullscreenMapImageDimensions();
                $this->addJavascriptFullscreenDynamicMap();
            }
        }
        
        $this->assign('fullscreen', $fullscreen);
        $this->assign('isStatic', $imgController->isStatic());
        
        $this->initializeMapElements('mapimage', $imgController, $imageWidth, $imageHeight);

        // call the function that updates the image size        
        if ($fullscreen && $imgController->isStatic()) {
            $this->addJavascriptFullscreenStaticMap();
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
                $searchResults = $mapSearch->searchByProximity($center, 1000, 10, $dataController);
                $places = array();
                if ($searchResults) {
                    foreach ($searchResults as $result) {
                        if ($result->getId() !== $currentId || $result->getTitle() !== $currentTitle) {
                            $places[] = $this->linkForItem($result);
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

                $photoFile = $this->photoFileForPlacemark($feature);
                if ($photoFile) {
                    $tabJavascripts[$tabKey] = "loadImage(photoURL,'photo');";
                    $photoURL = $photoServer.rawurlencode($photoFile);
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
            case 'categories':
            {
                // TODO generate a list of categories related to this placemark
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

                if ($this->feedGroup === null && $this->numGroups > 1) {
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

                    $groupAlias = $this->getOptionalModuleVar('GROUP_ALIAS', 'Campus');
                    $this->assign('browseHint', "Select a $groupAlias");
                    $this->assign('categories', $categories);

                    $this->addOnLoad('sortGroupsByDistance();');
                    
                } else {
                    $groupData = $this->getDataForGroup($this->feedGroup);
                    $browseBy = $groupData['title'];
                    if ($this->numGroups > 1) {
                        $cookieID = http_build_query(array('group' => $this->feedGroup));
                        $this->generateBookmarkOptions($cookieID);

                        $groupAlias = $this->getOptionalModuleVar('GROUP_ALIAS_PLURAL', 'Campuses');
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
                    $this->assign('browseHint', "Browse {$browseBy} by:");
                    $this->assign('searchTip', "You can search by any category shown in the 'Browse by' list below.");
                }

                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->generateBookmarkLink();
                }

                break;
            
            case 'bookmarks':
                if (!$this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->redirectTo('index', array());
                }
                $feedGroups = array();
                $places = array();

                foreach ($this->getBookmarks() as $aBookmark) {
                    if ($aBookmark) { // prevent counting empty string
                        $titles = $this->getTitleForBookmark($aBookmark);
                        $subtitle = count($titles) > 1 ? $titles[1] : null;
                        if ($this->bookmarkType($aBookmark) == 'group') {
                            $feedGroups[] = array(
                                'title' => $titles[0],
                                'subtitle' => $subtitle,
                                'url' => $this->detailURLForBookmark($aBookmark),
                                );
                        } else {
                            $places[] = array(
                                'title' => $titles[0],
                                'subtitle' => $subtitle,
                                'url' => $this->detailURLForBookmark($aBookmark),
                                );
                        }                        
                    }
                }
                $this->assign('groupAlias', $this->getOptionalModuleVar('GROUP_ALIAS_PLURAL', 'Campuses'));
                $this->assign('groups', $feedGroups);
                $this->assign('places', $places);
            
                break;
            
            case 'search':
          
                if (isset($this->args['filter'])) {
                    $this->feedGroup = null;
                    $searchTerms = $this->args['filter'];

                    // TODO: redirect if there is one result
                    $args = array_merge($this->args, array('addBreadcrumb' => true));

                    // still need a way to show the Google logo if we use their search
                    $places = $this->searchItems($searchTerms, null, $args);
        
                    $this->assign('searchTerms', $searchTerms);
                    $this->assign('places',      $places);
                  
                } else {
                  $this->redirectTo('index');
                }
                break;
            
            case 'category':

                $categoryPath = $this->getCategory();
                if ($categoryPath) {
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
                            $url = $this->detailURL($listItem->getId(), $categoryPath);
                        } else {
                            // for folder objects, getIndex returns the subcategory ID
                            //$drilldownPath = array_merge($categoryPath, array($listItem->getId()));
                            $drilldownPath = array_merge($this->getDrillDownPath(), array($listItem->getId()));
                            $url = $this->categoryURL($categoryPath, $drilldownPath, false);
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
                            $groupAlias = $this->getOptionalModuleVar('GROUP_ALIAS_PLURAL', 'Campuses');
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

                $dataController = $this->getDataController();
                $drilldownPath = $this->getDrillDownPath();
                if ($drilldownPath) {
                    $dataController->addDisplayFilter('category', $drilldownPath);
                }
                if ($this->featureIndex !== null) {
                    $feature = $dataController->selectFeature($this->featureIndex);

                } elseif (isset($this->args['lat'], $this->args['lon'])) {
                    $lat = $this->args['lat'];
                    $lon = $this->args['lon'];
                    $feature = new BasePlacemark(
                        new MapBasePoint(array(
                            'lat' => $lat,
                            'lon' => $lon,
                            )));
                    $feature->setTitle($this->getArg('title', "$lat,$lon"));
                    // hacky
                    $dataController->setSelectedFeatures(array($feature));
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

                    } elseif (isset($this->args['group'])) {
                        // TODO: this branch may not be being used
                        $cookieID = http_build_query(array('group' => $this->args['group']));
                        $this->generateBookmarkOptions($cookieID);
                    }
                }
                
                if ($feature) {
                    $title = $feature->getTitle();
                    // prevent infinite loop in smarty_modifier_replace
                    // TODO figure out why smarty gets in an infinite loop
                    $address = str_replace("\n", " ", $feature->getSubtitle());
                } else {
                    // TODO put something reasonable here
                    $title = '';
                    $address = '';
                }
                $this->assign('name', $this->getArg('title', $title));
                $this->assign('address', $this->getArg('address', $address));
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
                $dataController->selectFeature($this->featureIndex);
                $this->initializeMap($dataController, true);
                break;
        }
    }
}
