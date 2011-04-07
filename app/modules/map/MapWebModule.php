<?php

includePackage('Maps');

define('MAP_GROUP_COOKIE', 'mapgroup');

class MapWebModule extends WebModule {

    protected $id = 'map';
    protected $bookmarkLinkTitle = 'Bookmarked Locations';
    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $numGroups = 1;
    protected $feeds;
    
    private function getDataForGroup($group) {
        if (!$this->feedGroups) {
             $this->feedGroups = $this->getFeedGroups();
        }
        return isset($this->feedGroups[$group]) ? $this->feedGroups[$group] : null;
    }
    
    public function getFeedGroups() {
        return $this->getModuleSections('feedgroups');
    }
    
    private function getCategoriesAsArray() {
        $category = $this->getArg('category', null);
        if ($category !== null) {
            return explode(MAP_CATEGORY_DELIMITER, $category);
        }
        return array();
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
                $data[$this->feedGroup.MAP_CATEGORY_DELIMITER.$id] = $feedData;;
            }

        } else {
            foreach ($this->feedGroups as $groupID => $groupData) {
                $configName = "feeds-$groupID";
                foreach ($this->getModuleSections($configName) as $id => $feedData) {
                    $data[$groupID.MAP_CATEGORY_DELIMITER.$id] = $feedData;
                }
            }
        }

        return $data;
    }

    protected function getModuleAdminSections() {
        $sections = parent::getModuleAdminSections();
        
        foreach ($this->getFeedGroups() as $feedgroup=>$data) {
            $sections['feeds-'.$feedgroup] = $data['title'];
        }
        
        return $sections;
    }
    
    protected function getModuleAdminConfig() {
        $configData = parent::getModuleAdminConfig();
        
        foreach ($this->getFeedGroups() as $feedgroup=>$data) {
            $feedData = $configData['feed'];
            $feedData['title'] = $data['title'];
            $feedData['config'] = 'feeds-' . $feedgroup;
            $configData['feeds-'.$feedgroup] = $feedData;
        }
        unset($configData['feed']);
        
        return $configData;
    }

    
    protected function initialize() {
        $this->feedGroup = $this->getArg('group', NULL);
        if ($this->feedGroup === NULL) {
            if (isset($_COOKIE[MAP_GROUP_COOKIE])) {
                $this->feedGroup = $_COOKIE[MAP_GROUP_COOKIE];
            }
        }

        $this->feedGroups = $this->getFeedGroups();
        $this->numGroups = count($this->feedGroups);

        // clear out invalid feed group argument
        if ($this->feedGroup !== NULL && $this->getDataForGroup($this->feedGroup) === NULL) {
            $this->feedGroup = NULL;
        }
    }
    
    protected function pageSupportsDynamicMap() {
        return ($this->pagetype == 'compliant' ||
                $this->pagetype == 'tablet')
            && $this->platform != 'blackberry'
            && $this->platform != 'bbplus';
    }

    protected function staticMapImageDimensions() {
        switch ($this->pagetype) {
            case 'tablet':
                $imageWidth = 600; $imageHeight = 350;
                break;
            case 'compliant':
                if ($this->platform == 'bbplus') {
                    $imageWidth = 410; $imageHeight = 260;
                } else {
                    $imageWidth = 290; $imageHeight = 290;
                }
                break;
            case 'touch':
            case 'basic':
                $imageWidth = 200; $imageHeight = 200;
                break;
        }
        return array($imageWidth, $imageHeight);
    }
    
    protected function dynamicMapImageDimensions() {
        $imageWidth = '98%';
        switch ($this->pagetype) {
            case 'tablet':
                $imageHeight = 350;
                break;
            case 'compliant':
            default:
                if ($this->platform == 'bbplus') {
                    $imageHeight = 260;
                } else {
                    $imageHeight = 290;
                }
                break;
        }
        return array($imageWidth, $imageHeight);
    }
    
    protected function fullscreenMapImageDimensions() {
        $imageWidth = '100%';
        $imageHeight = '100%';
        return array($imageWidth, $imageHeight);
    }

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

    protected function addJavascriptFullscreenRotateScreen() {
        $this->addOnOrientationChange('rotateScreen();');
    }

    protected function initializeMapElements($mapElement, $imgController, $imageWidth, $imageHeight) {
        $imgController->setImageWidth($imageWidth);
        $imgController->setImageHeight($imageHeight);
            
        if ($imgController->isStatic()) {
            $this->assign('imageUrl', $imgController->getImageURL());

            $this->assign('scrollNorth', $this->detailUrlForPan('n', $imgController));
            $this->assign('scrollEast', $this->detailUrlForPan('e', $imgController));
            $this->assign('scrollSouth', $this->detailUrlForPan('s', $imgController));
            $this->assign('scrollWest', $this->detailUrlForPan('w', $imgController));

            $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $imgController));
            $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $imgController));

            if ($this->pagetype == 'compliant' || $this->pagetype == 'tablet') {
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
    }
    
    private function initializeMap(MapDataController $dataController, MapFeature $feature, $fullscreen=FALSE) {
        
        $style = $feature->getStyle();
        $geometries = array();
        
        $geometries[] = $feature->getGeometry();

        // zoom
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } else {
            $zoomLevel = $dataController->getDefaultZoomLevel();
        }

        if ($this->pageSupportsDynamicMap() && $dataController->supportsDynamicMap()) {
            $imgController = $dataController->getDynamicMapController();
        } else {
            $imgController = $dataController->getStaticMapController();
        }

        if ($imgController->supportsProjections()) {
            $imgController->setDataProjection($dataController->getProjection());
        } else {
            $dataProjection = $dataController->getProjection();
            $outputProjection = $imgController->getMapProjection();
            if ($dataProjection != $outputProjection) {
                $projector = new MapProjector();
                $projector->setSrcProj($dataProjection);
                $projector->setDstProj($outputProjection);
                foreach ($geometries as $i => $geometry) {
                    $geometries[$i] = $projector->projectGeometry($geometry);
                }
            }
        }
        
        if (isset($this->args['lat'], $this->args['lon'])) {
            array_unshift($geometries, new EmptyMapPoint($this->args['lat'], $this->args['lon']));
        }
        
        // center
        if (isset($this->args['center'])) {
            $latlon = explode(",", $this->args['center']);
            $center = array('lat' => $latlon[0], 'lon' => $latlon[1]);
        } else {
            $center = $geometries[0]->getCenterCoordinate();
        }

        $imgController->setCenter($center);
        $imgController->setZoomLevel($zoomLevel);

        foreach ($geometries as $i => $geometry) {
            if ($geometry instanceof MapPolygon) {
                if ($imgController->canAddPolygons()) {
                    $imgController->addPolygon($geometry->getRings(), $style);
                }
            } elseif ($geometry instanceof MapPolyline) {
                if ($imgController->canAddPaths()) {
                    $imgController->addPath($geometry->getPoints(), $style);
                }
            } else {
                if ($imgController->canAddAnnotations()) {
                    $imgController->addAnnotation($geometry->getCenterCoordinate(), $style, $feature->getTitle());
                }
            }
        }

        if (!$fullscreen) {
            $this->assign('fullscreenURL', $this->buildBreadcrumbURL('fullscreen', $this->args, false));
        
            if ($imgController->isStatic()) {
                list($imageWidth, $imageHeight) = $this->staticMapImageDimensions();

            } else {
                list($imageWidth, $imageHeight) = $this->dynamicMapImageDimensions();
                $this->addInlineJavascriptFooter("\n hideMapTabChildren();\n");
            }
            
        } else {
            $this->assign('detailURL', $this->buildBreadcrumbURL('detail', $this->args, false));
            if ($imgController->isStatic()) {
                list($imageWidth, $imageHeight) = $this->staticMapImageDimensions();

            } else {
                list($imageWidth, $imageHeight) = $this->fullscreenMapImageDimensions();
                $this->addJavascriptFullscreenDynamicMap();
            }
            $this->addJavascriptFullscreenRotateScreen();
        }
        
        $this->assign('fullscreen', $fullscreen);
        $this->assign('isStatic', $imgController->isStatic());
        
        $this->initializeMapElements('mapimage', $imgController, $imageWidth, $imageHeight);

        // call the function that updates the image size        
        if ($fullscreen && $imgController->isStatic()) {
            $this->addJavascriptFullscreenStaticMap();
        }
    }
    
    // url builders

    // $category can be a string or array which specifies the drilldown path 
    // if null, user will be redirected to index
    private function categoryURL($category=null, $addBreadcrumb=true) {
        $args = array();
        if ($category !== NULL) {
            if (is_array($category)) {
                $category = implode(MAP_CATEGORY_DELIMITER, $category);
            }
            $args['category'] = $category;
        }
        return $this->buildBreadcrumbURL('category', $args, $addBreadcrumb);
    }
    
    private function groupURL($group, $addBreadcrumb=false) {
        $args = $this->args;
        $args['group'] = $group;
        $args['action'] = ($group == '') ? 'remove' : 'add';
        return $this->buildBreadcrumbURL('index', $args, $addBreadcrumb);
    }

    private function detailURL($name, $category=null, $addBreadcrumb=true) {
        $args = array();
        $args['featureindex'] = $name;
        if ($category) {
            if (is_array($category)) {
                $category = implode(MAP_CATEGORY_DELIMITER, $category);
            }
            $args['category'] = $category;
        }
        return $this->buildBreadcrumbURL('detail', $args, $addBreadcrumb);
    }
  
    private function detailURLForResult($urlArgs, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('detail', $urlArgs, $addBreadcrumb);
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

    public function federatedSearch($searchTerms, $maxCount, &$results) {
        $mapSearch = $this->getSearchClass();
        $searchResults = array_values($mapSearch->searchCampusMap($searchTerms));
        
        $limit = min($maxCount, count($searchResults));
        for ($i = 0; $i < $limit; $i++) {
            $result = array(
                'title' => $searchResults[$i]->getTitle(),
                'url'   => $this->buildBreadcrumbURL('detail',
                               shortArrayFromMapFeature($searchResults[$i]), false),
              );
              $results[] = $result;
        }
    
        return count($searchResults);
    }

    private function getDataController($categoryPath, &$listItemPath) {
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        if ($categoryPath === NULL) {
            return MapDataController::factory('MapDataController', array(
                'JS_MAP_CLASS' => 'GoogleJSMap',
                'DEFAULT_ZOOM_LEVEL' => $this->getOptionalModuleVar('DEFAULT_ZOOM_LEVEL', 10)
                ));
        
        } else {
            $listItemPath = $categoryPath;
            if ($this->numGroups > 0) {
                if (count($categoryPath) < 2) {
                    $path = implode(MAP_CATEGORY_DELIMITER, $categoryPath);
                    throw new Exception("invalid category path $path for multiple feed groups");
                }
                $feedIndex = array_shift($listItemPath).MAP_CATEGORY_DELIMITER.array_shift($listItemPath);
            } else {
                $feedIndex = array_shift($listItemPath);
            }
            $feedData = $this->feeds[$feedIndex];
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategory($feedIndex);
            $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
            return $controller;
        }
    }

    protected function getSearchClass() {
        $mapSearchClass = $this->getOptionalModuleVar('MAP_SEARCH_CLASS', 'MapSearch');
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();
        $mapSearch = new $mapSearchClass($this->feeds);
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
                'title' => $feed['TITLE'],
                'subtitle' => $subtitle,
                'url' => $this->categoryURL($id),
                );
        }
        $this->assign('categories', $categories);
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
            $dataController = $this->getDataController($categoryPath, $listItemPath);
            $feature = $dataController->getFeature($index, $listItemPath);
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

    // return true on success, false on failure
    protected function generateTabForKey($tabKey, $feature, $dataController, &$tabJavascripts) {
        switch ($tabKey) {
            case 'map':
            {
                $this->initializeMap($dataController, $feature);
                return true;
            }
            case 'nearby':
            {
                $geometry = $feature->getGeometry();
                $center = $geometry->getCenterCoordinate();
                
                $mapSearch = $this->getSearchClass();
                $searchResults = $mapSearch->searchByProximity($center, 1000, 10);
                $places = array();
                if ($searchResults) {
                    foreach ($searchResults as $result) {
                        // TODO eliminate current feature from results
                        $urlArgs = shortArrayFromMapFeature($result);
                        $place = array(
                            'title' => $result->getTitle(),
                            'subtitle' => $result->getSubtitle(),
                            'url' => $this->detailURLForResult($urlArgs, false),
                            );
                        $places[] = $place;
                    }
                    $this->assign('nearbyResults', $places);
                }
                return count($places) > 0;
            }
            case 'info':
            {
                // embedded photo
                $photoServer = $this->getOptionalModuleVar('MAP_PHOTO_SERVER');
                // this method of getting photo url is harvard-specific and
                // further only works on data for ArcGIS features.
                // TODO rewrite this if we find an alternate way to server photos
                if ($photoServer) {
                    $photoFile = $feature->getField('Photo');
                    if (isset($photoFile) && $photoFile != 'Null') {
                        $tabJavascripts[$tabKey] = "loadImage(photoURL,'photo');";
                        $photoURL = $photoServer.$photoFile;
                        $this->assign('photoURL', $photoURL);
                        $this->addInlineJavascript("var photoURL = '{$photoURL}';");
                    }
                }
                
                if (is_subclass_of($dataController, 'ArcGISDataController')) {
                    $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');   
                    $feature->setBlackList($detailConfig['details']['suppress']);
                }
                
                $displayDetailsAsList = $feature->getDescriptionType() == MapFeature::DESCRIPTION_LIST;
                $details = $feature->getDescription();
                
                $this->assign('displayDetailsAsList', $displayDetailsAsList);
                $this->assign('details', $details);
                
                return is_array($details) ? count($details) > 0 : strlen(trim($details));
            }
            default:
                break;
        }
        
        return false;
    }

    protected function initializeForPage() {

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
                            );
                    }
                    $groupAlias = $this->getOptionalModuleVar('GROUP_ALIAS', 'Campus');
                    $this->assign('browseHint', "Select a $groupAlias");
                    $this->assign('categories', $categories);
                    $this->assign('searchTip', NULL);
                    
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
                    $this->assignCategories();
                    $this->assign('browseHint', "Browse {$browseBy} by:");
                    $this->assign('searchTip', "You can search by any category shown in the 'Browse by' list below.");
                }

                $this->generateBookmarkLink();

                break;
            
            case 'bookmarks':
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
                    $searchTerms = $this->args['filter'];
                    $mapSearch = $this->getSearchClass();
                    $searchResults = $mapSearch->searchCampusMap($searchTerms);
        
                    if (count($searchResults) == 1) {
                        $this->redirectTo('detail', shortArrayFromMapFeature($searchResults[0]));
                    } else {
                        $places = array();
                        foreach ($searchResults as $result) {
                            $title = $result->getTitle();
                            $subtitle = $result->getSubtitle();
                            $place = array(
                                'title' => $title,
                                'subtitle' => $subtitle,
                                'url' => $this->detailURLForResult(shortArrayFromMapFeature($result)),
                            );
                            $places[] = $place;
                        }
                    }
        
                    $this->assign('searchTerms', $searchTerms);
                    $this->assign('places',      $places);
                  
                } else {
                  $this->redirectTo('index');
                }
                break;
            
            case 'category':

                $categoryPath = $this->getCategoriesAsArray();
                if ($categoryPath) {
                    // populate drop-down list at the bottom
                    $this->assignCategories();
        
                    // build the drill-down list
                    $dataController = $this->getDataController($categoryPath, $listItemPath);
                    $listItems = $dataController->getListItems($listItemPath);
                    if (count($listItems) == 1 && $listItems[0] instanceof MapFeature) {
                        $args = $this->args;
                        $args['featureindex'] = $listItems[0]->getIndex();
                        $this->redirectTo('detail', $args, true);
                    }

                    $places = array();
                    foreach ($listItems as $listItem) {
                        if ($listItem instanceof MapFeature) {
                            $url = $this->detailURL($listItem->getIndex(), $categoryPath);
                        } else {
                            // for folder objects, getIndex returns the subcategory ID
                            $drilldownPath = array_merge($categoryPath, array($listItem->getIndex()));
                            $url = $this->categoryURL($drilldownPath, false); // don't add breadcrumb
                        }
                        $places[] = array(
                            'title'    => $listItem->getTitle(),
                            'subtitle' => $listItem->getSubtitle(),
                            'url'      => $url,
                            );
                    }
                    $this->assign('title',  $dataController->getTitle());
                    $this->assign('places', $places);          
                  
                } else {
                      $this->redirectTo('index');
                }
                break;
          
            case 'detail':
                $detailConfig = $this->loadPageConfigFile('detail', 'detailConfig');        
                $tabKeys = array();
                $tabJavascripts = array();

                $dataController = $this->getDataControllerForMap($listItemPath);
                $feature = $this->getFeatureForMap($dataController, $listItemPath);
    
                if (isset($this->args['featureindex'])) { // this is a regular place
                    $cookieParams = array(
                        'category' => $this->args['category'],
                        'featureindex' => $this->args['featureindex'],
                        );
                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);

                } elseif (isset($this->args['group'])) {
                    $cookieID = http_build_query(array('group' => $this->args['group']));
                    $this->generateBookmarkOptions($cookieID);
                }
                
                $this->assign('name', $this->getArg('title', $feature->getTitle()));
                // prevent infinite loop in smarty_modifier_replace
                // TODO figure out why smarty gets in an infinite loop
                $address = str_replace("\n", " ", $feature->getSubtitle());
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
                $dataController = $this->getDataControllerForMap();
                $feature = $this->getFeatureForMap($dataController);
                $this->initializeMap($dataController, $feature, true);
                break;
        }
    }
    
    private function getDataControllerForMap(&$listItemPath=array()) {
        if (isset($this->args['featureindex'])) { // this is a regular place
            $topCategory = NULL;
            $categoryPath = $this->getCategoriesAsArray();
            if (count($categoryPath)) {
                $topCategory = $categoryPath[0];
            }
            $dataController = $this->getDataController($categoryPath, $listItemPath);
                    
        } else {
            $dataController = $this->getDataController(NULL, $listItemPath);
        }
        return $dataController;
    }
        
    private function getFeatureForMap($dataController, $categoryPath=array()) {
        if (isset($this->args['featureindex'])) { // this is a regular place
            $index = $this->args['featureindex'];
            $feature = $dataController->getFeature($index, $categoryPath);
                    
        } elseif (isset($this->args['group'])) { // this is a campus
            $campusData = $this->getDataForGroup($this->args['group']);
            $coordParts = explode(',', $campusData['center']);
            $center = array('lat' => $coordParts[0], 'lon' => $coordParts[1]);

            $feature = new EmptyMapFeature($center);
            // may get rid of these setters and only allow setting in the constructor
            $feature->setTitle($campusData['title']);
            $feature->setField('address', $campusData['address']);
            $feature->setDescription($campusData['description']);
            $feature->setIndex($this->args['campus']);
        } else {
            $center = array('lat' => 0, 'lon' => 0);
            $feature = new EmptyMapFeature($center);
        }
        return $feature;
    }
}
