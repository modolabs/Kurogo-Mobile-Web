<?php

require_once LIB_DIR . '/Maps/MapDataController.php';
require_once LIB_DIR . '/Maps/MapFeature.php';
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


class MapModule extends Module {

    protected $id = 'map';
    protected $feeds;
    
    private function pageSupportsDynamicMap() {
        return $this->pagetype == 'compliant'
            && $this->platform != 'blackberry'
            && $this->platform != 'bbplus';
    }
    
    private function initializeMap(MapDataController $dataController, MapFeature $feature) {
        
        $style = $feature->getStyle();
        $geometry = $feature->getGeometry();

        // center
        if (isset($this->args['center'])) {
            $latlon = explode(",", $this->args['center']);
            $center = array('lat' => $latlon[0], 'lon' => $latlon[1]);
        } else {
            $center = $geometry->getCenterCoordinate();
        }

        // zoom
        if (isset($this->args['zoom'])) {
            $zoomLevel = $this->args['zoom'];
        } else {
            $zoomLevel = $dataController->getDefaultZoomLevel();
        }

        // image size
        switch ($this->pagetype) {
            case 'tablet':
                $imageWidth = '98%'; $imageHeight = 350;
                break;
            case 'compliant':
                if ($GLOBALS['deviceClassifier']->getPlatform() == 'bbplus') {
                    $imageWidth = 410; $imageHeight = 260;
                } else {
                    $imageWidth = '98%'; $imageHeight = 290;
                }
                break;
            case 'touch':
            case 'basic':
                $imageWidth = 200; $imageHeight = 200;
                break;
        }
        $this->assign('imageHeight', $imageHeight);
        $this->assign('imageWidth',  $imageWidth);

        $imgControllers = array();
        $imgControllers[] = $dataController->getStaticMapController();
        if ($this->pageSupportsDynamicMap() && $dataController->supportsDynamicMap()) {
            $imgControllers[] = $dataController->getDynamicMapController();
        }

        foreach ($imgControllers as $imgController) {

            if ($imgController->supportsProjections()) {
                $imgController->setDataProjection($dataController->getProjection());
            }
            
            $imgController->setCenter($center);
            $imgController->setZoomLevel($zoomLevel);

            switch ($geometry->getType()) {
                case MapGeometry::POINT:
                    if ($imgController->canAddAnnotations()) {
                        $imgController->addAnnotation($center['lat'], $center['lon'], $style, $feature->getTitle());
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

            } else {
                $imgController->setMapElement('mapimage');
                $this->addExternalJavascript($imgController->getIncludeScript());
                $this->addInlineJavascript($imgController->getHeaderScript());
                $this->addInlineJavascriptFooter($imgController->getFooterScript());
            }
        }
    }

    // TODO finish this
    private function initializeFullscreenMap() {
        $featureIndex = $this->args['featureindex'];
    }
  
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
  
    private function detailURLForFederatedSearchResult($result, $addBreadcrumb=true) {
        return $this->buildBreadcrumbURL('detail', $this->detailURLArgsForResult($result), $addBreadcrumb);
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
                'DEFAULT_ZOOM_LEVEL' => 10
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
            if ($campusID && !isset($feed['CAMPUS']) || $feed['CAMPUS'] != $campusID) continue;
            $subtitle = isset($feed['SUBTITLE']) ? $feed['SUBTITLE'] : null;
            $categories[] = array(
                'title' => $feed['TITLE'],
                'subtitle' => $subtitle,
                'url' => $this->categoryURL($id),
                );
        }
        $this->assign('categories', $categories);
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
                    $this->assign('browseHint', 'Browse Campuses:');
                    $this->assign('categories', $campusLinks);

                } else {
                    $this->assignCategoriesForCampus(NULL);
                    $this->assign('browseHint', 'Browse map by:');
                }
        
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
                
                break;
            
            case 'search':
          
                if (isset($this->args['filter'])) {
                    $searchTerms = $this->args['filter'];

                    // need more standardized var name for this config
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
                            $url = $this->categoryURL($category, $listItem->getIndex());
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
                
                // Map Tab
                $tabKeys[] = 'map';

                $hasMap = true;
                $this->assign('hasMap', $hasMap);

                if (!$this->feeds)
                    $this->feeds = $this->loadFeedData();
                
                if (isset($this->args['featureindex'])) { // this is a regular place
                    $index = $this->args['featureindex'];
                    $dataController = $this->getDataController($this->args['category']);
                    $subCategory = isset($this->args['subcategory']) ? $this->args['subcategory'] : null;
                    $feature = $dataController->getFeature($index, $subCategory);
                } else { // this is a campus
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
                }
                $this->initializeMap($dataController, $feature);
        
                $this->assign('name', $feature->getTitle());
                $this->assign('address', $feature->getSubtitle());
        
                // Photo Tab
                $photoServer = $GLOBALS['siteConfig']->getVar('MAP_PHOTO_SERVER');
                // this method of getting photo url is harvard-specific and
                // further only works on data for ArcGIS features.
                // TODO allow map controllers to determine what to put in the tabs
                if ($photoServer) {
                    $photoFile = $feature->getField('Photo');
                    if (isset($photoFile) && $photoFile != 'Null') {
                        $tabKeys[] = 'photo';
                        $tabJavascripts['photo'] = "loadPhoto(photoURL,'photo');";
                        $photoUrl = $photoServer.$photoFile;
                        $this->assign('photoUrl', $photoUrl);
                        $this->addInlineJavascript("var photoURL = '{$photoUrl}';");
                    }
                }
                
                // Details Tab
                $tabKeys[] = 'detail';
                if (is_subclass_of($dataController, 'ArcGISDataController')) {
                    $feature->setBlackList($detailConfig['details']['suppress']);
                }
                
                $displayDetailsAsList = $feature->getDescriptionType() == MapFeature::DESCRIPTION_LIST;
                $this->assign('displayDetailsAsList', $displayDetailsAsList);
                $this->assign('details', $feature->getDescription());
        
                $this->enableTabs($tabKeys, null, $tabJavascripts);
                break;
            
            case 'fullscreen':
                $this->initializeFullscreenMap();
                break;
        }
    }
}
