<?php

require_once LIB_DIR . '/Maps/MapFeature.php';
require_once LIB_DIR . '/Maps/MapDataController.php';
require_once LIB_DIR . '/Maps/MapImageController.php';
require_once LIB_DIR . '/Maps/Polyline.php';

require_once LIB_DIR . '/Maps/JavascriptMapImageController.php';
require_once LIB_DIR . '/Maps/StaticMapImageController.php';

require_once LIB_DIR . '/Maps/ArcGISDataController.php';
require_once LIB_DIR . '/Maps/ArcGISJSMap.php';
require_once LIB_DIR . '/Maps/ArcGISParser.php';
require_once LIB_DIR . '/Maps/ArcGISStaticMap.php';
require_once LIB_DIR . '/Maps/GoogleJSMap.php';
require_once LIB_DIR . '/Maps/GoogleStaticMap.php';
require_once LIB_DIR . '/Maps/KMLDataController.php';
require_once LIB_DIR . '/Maps/KMLDataParser.php';
require_once LIB_DIR . '/Maps/MapProjector.php';
require_once LIB_DIR . '/Maps/MapSearch.php';
require_once LIB_DIR . '/Maps/WMSDataParser.php';
require_once LIB_DIR . '/Maps/WMSStaticMap.php';


class MapWebModule extends WebModule {

    protected $id = 'map';
    protected $feeds;
    
    private function pageSupportsDynamicMap() {
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
        if (isset($this->args['lat'], $this->args['lon'])) {
            $geometry = new EmptyMapPoint($this->args['lat'], $this->args['lon']);
        } else {
            $geometry = $feature->getGeometry();
        }

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
                $geometry = $projector->projectGeometry($geometry);
            }
        }
        
        // center
        if (isset($this->args['center'])) {
            $latlon = explode(",", $this->args['center']);
            $center = array('lat' => $latlon[0], 'lon' => $latlon[1]);
        } else {
            $center = $geometry->getCenterCoordinate();
        }

        $imgController->setCenter($center);
        $imgController->setZoomLevel($zoomLevel);

        switch ($geometry->getType()) {
            case MapGeometry::POINT:
                if ($imgController->canAddAnnotations()) {
                    $imgController->addAnnotation($geometry->getCenterCoordinate(), $style, $feature->getTitle());
                }
                break;
            case MapGeometry::POLYLINE:
                if ($imgController->canAddPaths()) {
                    $imgController->addPath($geometry->getPoints(), $style);
                }
                break;
            case MapGeometry::POLYGON:
                if ($imgController->canAddPolygons()) {
                    $imgController->addPolygon($geometry->getRings(), $style);
                }
                break;
            default:
                break;
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
                $this->addInlineJavascriptFooter("\n hide('loadingimage');\n");
            }
            $this->addOnOrientationChange('rotateScreen();');
        }
        
        $this->assign('fullscreen', $fullscreen);
        $this->assign('isStatic', $imgController->isStatic());
        
        $this->initializeMapElements('mapimage', $imgController, $imageWidth, $imageHeight);

        // call the function that updates the image size        
        if ($fullscreen && $imgController->isStatic()) {
            // Let Webkit figure out what the window size is and then hide the address bar
            // and resize the map
            $this->addOnLoad('setTimeout(function () { window.scrollTo(0, 1); updateMapDimensions(); }, 1000);');
            $this->addOnOrientationChange('updateMapDimensions();');
        }
    }
    
    // url builders
  
    private function categoryURL($category=NULL, $subCategory=NULL, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('category', array(
            'category' => $category,
            'subcategory' => $subCategory,
        ), $addBreadcrumb);
    }
    
    private function campusURL($campusIndex, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('campus', array(
            'campus' => $campusIndex,
        ), $addBreadcrumb);
    }

    private function detailURL($name, $category, $subCategory=null, $info=null, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('detail', array(
            'featureindex' => $name,
            'category'     => $category,
            'subcategory'  => $subCategory,
            'info'         => $info,
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

    private function detailUrlForBBox($bbox=null) {
        $args = $this->args;
        if (isset($bbox)) {
            $args['bbox'] = $bbox;
        }
        return $this->buildBreadcrumbURL('detail', $args, false);
    }
  
    private function fullscreenUrlForBBox($bbox=null) {
        $args = $this->args;
        if (isset($bbox)) {
            $args['bbox'] = $bbox;
        }
        return $this->buildBreadcrumbURL('fullscreen', $args, false);
    }

    public function federatedSearch($searchTerms, $maxCount, &$results) {
        $mapSearchClass = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_CLASS');
        $mapSearch = new $mapSearchClass();
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();
        $mapSearch->setFeedData($this->feeds);
        $searchResults = array_values($mapSearch->searchCampusMap($searchTerms));
        
        $limit = min($maxCount, count($searchResults));
        for ($i = 0; $i < $limit; $i++) {
            $result = array(
                'title' => $mapSearch->getTitleForSearchResult($searchResults[$i]),
                'url'   => $this->buildBreadcrumbURL(
                               "/{$this->id}/detail",
                               $mapSearch->getURLArgsForSearchResult($searchResults[$i]), false),
              );
              $results[] = $result;
        }
    
        return count($searchResults);
    }

    private function getDataController($index) {
        if ($index === NULL) {
            return MapDataController::factory('MapDataController', array(
                'JS_MAP_CLASS' => 'GoogleJSMap',
                'DEFAULT_ZOOM_LEVEL' => $this->getModuleVar('DEFAULT_ZOOM_LEVEL', 10)
                ));
        
        } else if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
            return $controller;
        }
    }
    
    private function assignCategoriesForCampus($campusID=NULL) {
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
        setcookie($this->bookmarkCookie, $values, $expireTime);
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
            $dataController = $this->getDataController($params['category']);
            $subCategory = isset($params['subcategory']) ? $params['subcategory'] : null;
            $feature = $dataController->getFeature($index, $subCategory);
            return array($feature->getTitle(), $dataController->getTitle());
        
        } else if (isset($params['campus'])) {
            $campus = $GLOBALS['siteConfig']->getSection('campus-'.$params['campus']);
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
                
                $mapSearchClass = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_CLASS');
                $mapSearch = new $mapSearchClass();
                if (!$this->feeds)
                    $this->feeds = $this->loadFeedData();
                $mapSearch->setFeedData($this->feeds);
                
                $searchResults = $mapSearch->searchByProximity($center, 1000, 10);
                $places = array();
                if ($searchResults) {
                    foreach ($searchResults as $result) {
                        // TODO eliminate current feature from results
                        $title = $mapSearch->getTitleForSearchResult($result);
                        $urlArgs = $mapSearch->getURLArgsForSearchResult($result);
                        $place = array(
                            'title' => $title,
                            'subtitle' => isset($result['subtitle']) ? $result['subtitle'] : null,
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
                $photoServer = $GLOBALS['siteConfig']->getVar('MAP_PHOTO_SERVER');
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
                    $detailConfig = $this->loadWebAppConfigFile('map-detail', 'detailConfig');   
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
                $numCampuses = $GLOBALS['siteConfig']->getVar('CAMPUS_COUNT');
                if ($numCampuses > 1) {
                    $campusLinks = array();
                    for ($i = 0; $i < $numCampuses; $i++) {
                        $aCampus = $GLOBALS['siteConfig']->getSection('campus-'.$i);
                        $campusLinks[] = array(
                            'title' => $aCampus['title'],
                            'url' => $this->campusURL($i),
                            );
                    }
                    $this->assign('browseHint', 'Select a Location');
                    $this->assign('categories', $campusLinks);
                    $this->assign('searchTip', NULL);

                } else {
                    if (!$this->feeds)
                        $this->feeds = $this->loadFeedData();
                    
                    $this->assignCategoriesForCampus(NULL);
                    $this->assign('browseHint', 'Browse map by:');
                    $this->assign('searchTip', "You can search by any category shown in the 'Browse by' list below.");
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
            
            case 'campus':
                // this is like the index page for single-campus organizations
                if (!$this->feeds)
                    $this->feeds = $this->loadFeedData();
                
                $index = $this->args['campus'];
                $campus = $GLOBALS['siteConfig']->getSection('campus-'.$index);
                $title = $campus['title'];
                $id = $campus['id'];

                $this->assignCategoriesForCampus($id);
                $this->assign('browseHint', "Browse {$title} by:");

                $cookieID = http_build_query(array('campus' => $index));

                $this->generateBookmarkOptions($cookieID);

                $this->generateBookmarkLink();

                break;
            
            case 'search':
          
                if (isset($this->args['filter'])) {
                    $searchTerms = $this->args['filter'];

                    $mapSearchClass = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_CLASS');
                    $mapSearch = new $mapSearchClass();
                    if (!$this->feeds)
                        $this->feeds = $this->loadFeedData();
                    $mapSearch->setFeedData($this->feeds);
        
                    $searchResults = $mapSearch->searchCampusMap($searchTerms);
        
                    if (count($searchResults) == 1) {
                        $this->redirectTo('detail', $mapSearch->getURLArgsForSearchResult($searchResults[0]));
                    } else {
                        $places = array();
                        foreach ($searchResults as $result) {
                            $title = $mapSearch->getTitleForSearchResult($result);
                            $place = array(
                                'title' => $title,
                                'subtitle' => isset($result['subtitle']) ? $result['subtitle'] : null,
                                'url' => $this->detailURLForResult($mapSearch->getURLArgsForSearchResult($result)),
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
                    if (!$this->feeds)
                      $this->feeds = $this->loadFeedData();
        
                    // populate drop-down list at the bottom
                    $categories = array();
                    foreach ($this->feeds as $id => $feed) {
                        $categories[] = array(
                            'id' => $id,
                            'title' => $feed['TITLE'],
                            );
                    }
                    $this->assign('categories', $categories);
        
                    // build the drill-down list
                    $category = $this->args['category'];
                    $dataController = $this->getDataController($category);
        
                    if (isset($this->args['subcategory'])) {
                        $subCategory = $this->args['subcategory'];
                    } else {
                        $subCategory = null;
                    }
        
                    $listItems = $dataController->getListItems($subCategory);
        
                    $places = array();
                    foreach ($listItems as $listItem) {
                        if ($listItem instanceof MapFeature) {
                            $url = $this->detailURL($listItem->getIndex(), $category, $subCategory);
                        } else {
                            // for folder objects, getIndex returns the subcategory ID
                            $url = $this->categoryURL($category, $listItem->getIndex(), false); // don't add breadcrumb
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
                $detailConfig = $this->loadWebAppConfigFile('map-detail', 'detailConfig');        
                $tabKeys = array();
                $tabJavascripts = array();
                
                if (!$this->feeds)
                    $this->feeds = $this->loadFeedData();
                
                if (isset($this->args['featureindex'])) { // this is a regular place
                    $index = $this->args['featureindex'];
                    $dataController = $this->getDataController($this->args['category']);
                    $subCategory = isset($this->args['subcategory']) ? $this->args['subcategory'] : null;
                    $feature = $dataController->getFeature($index, $subCategory);
                    
                    $cookieParams = array(
                        'category' => $this->args['category'],
                        'subcategory' => $subCategory,
                        'featureindex' => $index,
                        );
                    $cookieID = http_build_query($cookieParams);
                    $this->generateBookmarkOptions($cookieID);
                    
                } elseif (isset($this->args['campus'])) { // this is a campus
                    $index = $this->args['campus'];
                    $campus = $GLOBALS['siteConfig']->getSection('campus-'.$index);
                    $coordParts = explode(',', $campus['center']);
                    $center = array('lat' => $coordParts[0], 'lon' => $coordParts[1]);

                    $dataController = $this->getDataController(NULL);
                    
                    $feature = new EmptyMapFeature($center);
                    $feature->setTitle($campus['title']);
                    $feature->setAddress($campus['address']);
                    $feature->setDescription($campus['description']);
                    $feature->setIndex($index);
                } else {
                    $center = array('lat' => 0, 'lon' => 0);
                    $feature = new EmptyMapFeature($center);
                    $dataController = $this->getDataController(NULL);
                    $cookieID = http_build_query($this->args);
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
                if (!$this->feeds)
                    $this->feeds = $this->loadFeedData();
                
                $index = $this->args['featureindex'];
                $dataController = $this->getDataController($this->args['category']);
                $subCategory = isset($this->args['subcategory']) ? $this->args['subcategory'] : null;
                $feature = $dataController->getFeature($index, $subCategory);

                $this->initializeMap($dataController, $feature, true);
                break;
        }
    }
}
