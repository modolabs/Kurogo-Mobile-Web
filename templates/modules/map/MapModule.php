<?php

require_once LIB_DIR . '/Maps/MapLayerDataController.php';
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
// TODO for this module:
// - terminology is bad:
//   * "category" and "layer" are used interchangeably when we
//      mean the thing that contains the image controller
//   * "selectvalues" is MIT legacy and we really mean a place identifier
// - need to write map image controllers in such a way that other modules
//   can incorporate map images without redoing work

    protected $id = 'map';
    protected $feeds;
    
    private function pageSupportsDynamicMap() {
        return $this->pagetype == 'compliant'
            && $this->platform != 'blackberry'
            && $this->platform != 'bbplus';
    }
    
    private function initializeMap(MapLayerDataController $layer, MapFeature $feature) {
        
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
            $zoomLevel = $layer->getDefaultZoomLevel();
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

        $mapControllers = array();
        $mapControllers[] = $layer->getStaticMapController();
        if ($this->pageSupportsDynamicMap() && $layer->supportsDynamicMap()) {
            $mapControllers[] = $layer->getDynamicMapController();
        }

        foreach ($mapControllers as $mapController) {

            if ($mapController->supportsProjections()) {
                $mapController->setDataProjection($layer->getProjection());
            }
            
            $mapController->setCenter($center);
            $mapController->setZoomLevel($zoomLevel);

            switch ($geometry->getType()) {
                case 'Point':
                    if ($mapController->canAddAnnotations()) {
                        $mapController->addAnnotation($center['lat'], $center['lon'], $style, $feature->getTitle());
                    }
                    break;
                case 'Polyline':
                    if ($mapController->canAddPaths()) {
                        $mapController->addPath($geometry->getPoints(), $style);
                    }
                    break;
                case 'Polygon':
                    if ($mapController->canAddPolygons()) {
                        $mapController->addPolygon($geometry->getRings(), $style);
                    }
                    break;
                default:
                    break;
            }

            $mapController->setImageWidth($imageWidth);
            $mapController->setImageHeight($imageHeight);

            if ($mapController->isStatic()) {

                $this->assign('imageUrl', $mapController->getImageURL());

                $this->assign('scrollNorth', $this->detailUrlForPan('n', $mapController));
                $this->assign('scrollEast', $this->detailUrlForPan('e', $mapController));
                $this->assign('scrollSouth', $this->detailUrlForPan('s', $mapController));
                $this->assign('scrollWest', $this->detailUrlForPan('w', $mapController));

                $this->assign('zoomInUrl', $this->detailUrlForZoom('in', $mapController));
                $this->assign('zoomOutUrl', $this->detailUrlForZoom('out', $mapController));

            } else {
                $mapController->setMapElement('mapimage');
                foreach($mapController->getIncludeScripts() as $includeScript) {
                    $this->addExternalJavascript($includeScript);
                }
                $this->addInlineJavascript($mapController->getHeaderScript());
                $this->addInlineJavascriptFooter($mapController->getFooterScript());
            }
        }
    }

    // TODO finish this
    private function initializeFullscreenMap() {
      $selectvalue = $this->args['selectvalues'];
    }
  
  private function categoryURL($category=NULL, $subCategory=NULL, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('category', array(
      'category' => $category,
      'subcategory' => $subCategory,
    ), $addBreadcrumb);
  }

  private function detailURL($name, $category, $subCategory=null, $info=null, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'selectvalues' => $name,
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
  
  private function detailUrlForPan($direction, $mapController) {
    $args = $this->args;
    $center = $mapController->getCenterForPanning($direction);
    $args['center'] = $center['lat'] .','. $center['lon'];
    return $this->buildBreadcrumbURL('detail', $args, false);
  }

  private function detailUrlForZoom($direction, $mapController) {
    $args = $this->args;
    $args['zoom'] = $mapController->getLevelForZooming($direction);
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
        'url'   => $this->buildBreadcrumbURL("/{$this->id}/detail",
            $mapSearch->getURLArgsForSearchResult($searchResults[$i]), false),
      );
      $results[] = $result;
    }

    return count($searchResults);
  }

    private function getLayer($index) {
        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            $controller = MapLayerDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
            return $controller;
        }
    }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
        
      case 'index':
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

        // TODO show category description in cell subtitles
        $this->assign('categories', $categories);
        break;
        
      case 'search':
      
        if (isset($this->args['filter'])) {
            $searchTerms = $this->args['filter'];

            // need more standardized var name for this config
            //$externalSearch = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_URL');
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
          $layer = $this->getLayer($category);

          if (isset($this->args['subcategory'])) {
            $subCategory = $this->args['subcategory'];
          } else {
            $subCategory = null;
          }

          $listItems = $layer->getListItems($subCategory);

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
          $this->assign('title',      $layer->getTitle());
          $this->assign('places',     $places);          
          
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

        $index = $this->args['selectvalues'];
        $layer = $this->getLayer($this->args['category']);
        $subCategory = isset($this->args['subcategory']) ? $this->args['subcategory'] : null;
        $feature = $layer->getFeature($index, $subCategory);
        $this->initializeMap($layer, $feature);

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
        if (is_subclass_of($layer, 'ArcGISDataController')) {
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
