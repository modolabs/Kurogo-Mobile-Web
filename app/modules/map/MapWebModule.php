<?php

includePackage('Maps');

class MapWebModule extends WebModule {

    protected $id = 'map';
    protected $feeds;
    
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
                if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
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
                $this->addInlineJavascript(
                    "mapWidth = $imageWidth;\n"
                    ."mapHeight = $imageHeight;\n"
                    ."staticMapOptions = "
                    .$imgController->getJavascriptControlOptions().";\n"
                    );
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
        return $this->buildBreadcrumbURL('category', array(
            'category' => $category,
        ), $addBreadcrumb);
    }
    
    private function campusURL($campusIndex, $addBreadcrumb=true) {
        $args = $this->args;
        $args['campus'] = $campusIndex;
        return $this->buildBreadcrumbURL('campus', $args, $addBreadcrumb);
    }

    private function detailURL($name, $categoryPath=null, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('detail', array(
            'featureindex' => $name,
            'category'     => $categoryPath,
        ), $addBreadcrumb);
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
        $mapSearchClass = $this->getModuleVar('MAP_SEARCH_CLASS');
        $mapSearch = new $mapSearchClass();
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();
        $mapSearch->setFeedData($this->feeds);
        $searchResults = array_values($mapSearch->searchCampusMap($searchTerms));
        
        $limit = min($maxCount, count($searchResults));
        for ($i = 0; $i < $limit; $i++) {
            $result = array(
                'title' => $searchResults[$i]->getTitle(),
                'url'   => $this->buildBreadcrumbURL(
                               "/{$this->id}/detail",
                               shortArrayFromMapFeature($searchResults[$i]), false),
              );
              $results[] = $result;
        }
    
        return count($searchResults);
    }

    private function getDataController($index) {
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        if ($index === NULL) {
            return MapDataController::factory('MapDataController', array(
                'JS_MAP_CLASS' => 'GoogleJSMap',
                'DEFAULT_ZOOM_LEVEL' => $this->getModuleVar('DEFAULT_ZOOM_LEVEL', 10)
                ));
        
        } else if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategoryId($index);
            $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
            return $controller;
        }
    }
    
    private function assignCategoriesForCampus($campusID=NULL) {
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        $categories = array();
        foreach ($this->feeds as $id => $feed) {
            if (isset($feed['HIDDEN']) && $feed['HIDDEN']) continue;
            if ($campusID && (!isset($feed['CAMPUS']) || $feed['CAMPUS'] != $campusID)) continue;
            $subtitle = isset($feed['SUBTITLE']) ? $feed['SUBTITLE'] : null;
            $categories[] = array(
                'title' => $feed['TITLE'],
                'subtitle' => $subtitle,
                'url' => $this->categoryURL($id),
                );
        }
        $this->assign('categories', $categories);
    }

    // bookmarks -- shouldn't really be specific to this module

    protected $bookmarkCookie = 'mapbookmarks';
    protected $bookmarkLifespan = 25237;

    protected function generateBookmarkOptions($cookieID) {
        // compliant branch
        $this->addOnLoad("setBookmarkStates('{$this->bookmarkCookie}', '{$cookieID}')");
        $this->assign('cookieName', $this->bookmarkCookie);
        $this->assign('expireDate', $this->bookmarkLifespan);
        $this->assign('bookmarkItem', $cookieID);

        // the rest of this is all touch and basic branch
        if (isset($this->args['bookmark'])) {
            if ($this->args['bookmark'] == 'add') {
                $this->addBookmark($cookieID);
                $status = 'on';
                $bookmarkAction = 'remove';
            } else {
                $this->removeBookmark($cookieID);
                $status = 'off';
                $bookmarkAction = 'add';
            }

        } else {
            if ($this->hasBookmark($cookieID)) {
                $status = 'on';
                $bookmarkAction = 'remove';
            } else {
                $status = 'off';
                $bookmarkAction = 'add';
            }
        }

        $this->assign('bookmarkStatus', $status);
        $this->assign('bookmarkURL', $this->bookmarkToggleURL($bookmarkAction));
        $this->assign('bookmarkAction', $bookmarkAction);
    }

    private function bookmarkToggleURL($toggle) {
        $args = $this->args;
        $args['bookmark'] = $toggle;
        return $this->buildBreadcrumbURL($this->page, $args, false);
    }

    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        if (isset($params['featureindex']) || isset($params['lat'], $params['lon'])) {
            return $this->buildBreadcrumbURL('detail', $params, true);
        } else if (isset($params['campus'])) {
            return $this->campusURL($params['campus']);
        } else {
            return '#';
        }
    }

    protected function getBookmarks() {
        $bookmarks = array();
        if (isset($_COOKIE[$this->bookmarkCookie])) {
            $bookmarks = explode(",", $_COOKIE[$this->bookmarkCookie]);
        }
        return $bookmarks;
    }

    protected function setBookmarks($bookmarks) {
        $values = implode(",", $bookmarks);
        $expireTime = time() + $this->bookmarkLifespan;
        setcookie($this->bookmarkCookie, $values, $expireTime, COOKIE_PATH);
    }

    protected function addBookmark($aBookmark) {
        $bookmarks = $this->getBookmarks();
        if (!in_array($aBookmark, $bookmarks)) {
            $bookmarks[] = $aBookmark;
            $this->setBookmarks($bookmarks);
        }
    }

    protected function removeBookmark($aBookmark) {
        $bookmarks = $this->getBookmarks();
        $index = array_search($aBookmark, $bookmarks);
        if ($index !== false) {
            array_splice($bookmarks, $index, 1);
            $this->setBookmarks($bookmarks);
        }
    }

    protected function hasBookmark($aBookmark) {
        return in_array($aBookmark, $this->getBookmarks());
    }
    
    protected function getTitleForBookmark($aBookmark) {
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();

        parse_str($aBookmark, $params);
        if (isset($params['featureindex'])) {
            $index = $params['featureindex'];
            $categoryPath = $params['category'];
            if (is_array($categoryPath)) {
                $topCategory = array_shift($categoryPath);
            } else {
                $topCategory = $categoryPath;
                $categoryPath = array();
            }
            $dataController = $this->getDataController($topCategory);
            $feature = $dataController->getFeature($index, $categoryPath);
            return array($feature->getTitle(), $dataController->getTitle());
        
        } else if (isset($params['campus'])) {
            $campus = $this->getDataForCampus($params['campus']);
            return array($campus['title']);

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
        if (isset($params['campus']))
            return 'campus';
        return 'place';
    }
    
    private function generateBookmarkLink() {
        $hasBookmarks = count($this->getBookmarks()) > 0;
        if ($hasBookmarks) {
            $bookmarkLink = array(array(
                'title' => 'Bookmarked Locations',
                'url' => $this->buildBreadcrumbURL('bookmarks', $this->args, true),
                ));
            $this->assign('bookmarkLink', $bookmarkLink);
        }
        $this->assign('hasBookmarks', $hasBookmarks);
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
                
                $mapSearchClass = $this->getModuleVar('MAP_SEARCH_CLASS');
                $mapSearch = new $mapSearchClass();
                if (!$this->feeds)
                    $this->feeds = $this->loadFeedData();
                $mapSearch->setFeedData($this->feeds);
                
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
                $photoServer = $this->getModuleVar('MAP_PHOTO_SERVER');
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
    
    private function getDataForCampus($campus) {
        $campuses = $this->getCampuses();
        return isset($campuses[$campus]) ? $campuses[$campus] : null;
    }
    
    protected function getCampuses() {
        $campuses = array();
        $config = $this->getConfig($this->id, 'campus');
        return $config->getSectionVars();
    }

    protected function initializeForPage() {
        switch ($this->page) {
            case 'help':
                break;
            
            case 'index':
            case 'campus':
                $campuses = $this->getCampuses();
                if (count($campuses)==1) {
                    $campus = key($campuses);
                } else {
                    $campus = $this->getArg('campus', NULL);
                }
                
                if (empty($campus) && count($campuses)>1) {
                    // show the list of campuses
                    foreach ($campuses as $id=>$campusData) {
                        $campusLinks[] = array(
                            'title' => $campusData['title'],
                            'url' => $this->campusURL($id),
                            );
                    }
                    $this->assign('browseHint', 'Select a Location');
                    $this->assign('categories', $campusLinks);
                    $this->assign('searchTip', NULL);

                } elseif ($campusData = $this->getDataForCampus($campus)) {
                    $browseBy = $campusData['title'];
                    $cookieID = http_build_query(array('campus' => $campus));
                    $this->generateBookmarkOptions($cookieID);
                    $this->assignCategoriesForCampus($campus);
                    $this->assign('browseHint', "Browse {$browseBy} by:");
                    $this->assign('searchTip', "You can search by any category shown in the 'Browse by' list below.");
                } else {
                    if (count($campuses)==0) {
                        throw new Exception("No campuses defined");
                    } else {
                        throw new Exception("Invalid campus $campus");
                    }
                }
                
                $this->generateBookmarkLink();

                break;
            
            case 'bookmarks':
                $campuses = array();
                $places = array();

                foreach ($this->getBookmarks() as $aBookmark) {
                    if ($aBookmark) { // prevent counting empty string
                        $titles = $this->getTitleForBookmark($aBookmark);
                        $subtitle = count($titles) > 1 ? $titles[1] : null;
                        if ($this->bookmarkType($aBookmark) == 'campus') {
                            $campuses[] = array(
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
                $this->assign('campuses', $campuses);
                $this->assign('places', $places);
            
                break;
            
            case 'search':
          
                if (isset($this->args['filter'])) {
                    $searchTerms = $this->args['filter'];

                    $mapSearchClass = $this->getModuleVar('MAP_SEARCH_CLASS');
                    $mapSearch = new $mapSearchClass();
                    if (!$this->feeds)
                        $this->feeds = $this->loadFeedData();
                    $mapSearch->setFeedData($this->feeds);
        
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
                if (isset($this->args['category'])) {
                    // populate drop-down list at the bottom
                    $this->assignCategoriesForCampus($this->getArg('campus', NULL));
        
                    // build the drill-down list
                    $categoryPath = $this->args['category'];
                    if (is_array($categoryPath)) {
                        $topCategory = array_shift($categoryPath);
                    } else {
                        $topCategory = $categoryPath;
                        $categoryPath = array();
                    }
                    $dataController = $this->getDataController($topCategory);
                    $listItems = $dataController->getListItems($categoryPath);
                    array_unshift($categoryPath, $topCategory); // restore the path since we still need it

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

                $dataController = $this->getDataControllerForMap();
                $feature = $this->getFeatureForMap($dataController);
    
                if (isset($this->args['featureindex'])) { // this is a regular place
                    $cookieParams = array(
                        'category' => $this->args['category'],
                        'featureindex' => $this->args['featureindex'],
                        );
                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);

                } elseif (isset($this->args['campus'])) {
                    $cookieID = http_build_query(array('campus' => $this->args['campus']));
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
    
    private function getDataControllerForMap() {
        if (isset($this->args['featureindex'])) { // this is a regular place
            $topCategory = $this->args['category'];
            if (is_array($topCategory)) {
                $topCategory = array_shift($topCategory);
            }
            $dataController = $this->getDataController($topCategory);
                    
        } else {
            $dataController = $this->getDataController(NULL);
        }
        return $dataController;
    }
        
    private function getFeatureForMap($dataController) {
        if (isset($this->args['featureindex'])) { // this is a regular place
            $index = $this->args['featureindex'];
            $categoryPath = $this->args['category'];
            if (is_array($categoryPath)) {
                array_shift($categoryPath);
            } else {
                $categoryPath = array();
            }
            $feature = $dataController->getFeature($index, $categoryPath);
                    
        } elseif (isset($this->args['campus'])) { // this is a campus
            $campusData = $this->getDataForCampus($this->args['campus']);
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
