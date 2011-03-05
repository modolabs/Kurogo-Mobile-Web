<?php

require_once LIB_DIR . '/Maps/MapFeature.php';
require_once LIB_DIR . '/Maps/MapDataController.php';

require_once LIB_DIR . '/Maps/ArcGISDataController.php';
require_once LIB_DIR . '/Maps/ArcGISParser.php';
require_once LIB_DIR . '/Maps/KMLDataController.php';
require_once LIB_DIR . '/Maps/KMLDataParser.php';
require_once LIB_DIR . '/Maps/MapProjector.php';
require_once LIB_DIR . '/Maps/MapSearch.php';

class MapAPIModule extends APIModule
{
    protected $id = 'map';
    protected $feeds = null;
    
    // from MapWebModule
    private function getDataController($index) {
        if (!$this->feeds) {
            $this->feeds = $this->loadFeedData();
        }
    
        if ($index === NULL) {
            return MapDataController::factory('MapDataController', array(
                'JS_MAP_CLASS' => 'GoogleJSMap',
                'DEFAULT_ZOOM_LEVEL' => $this->getModuleVar('DEFAULT_ZOOM_LEVEL', 10)
                ));
        
        } else if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategoryId($index);
            $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
            return $controller;
        }
    }
    
    private function getCategoriesForCampus($campusID=NULL) {
        $categories = array();
        foreach ($this->feeds as $id => $feedData) {
            if (isset($feedData['HIDDEN']) && $feedData['HIDDEN']) continue;
            if ($campusID && (!isset($feedData['CAMPUS']) || $feedData['CAMPUS'] != $campusID)) continue;
            
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $category = array(
                'id' => $id,
                'title' => $controller->getTitle(),
                );

            // TODO add these properties to the controller
            if (isset($feedData['SUBTITLE'])) {
                $category['subtitle'] = $feedData['SUBTITLE'];
            }
            if (isset($feedData['CAMPUS'])) {
                $category['campus'] = $feedData['CAMPUS'];
            }

            $listItems = $controller->getListItems();
            if (count($listItems)) {
                $listItem = $listItems[0]; // listItem implements MapListElement
                if (!($listItem instanceof MapFeature)) {
                    $category['subcategories'] = array();
                    foreach ($listItems as $listItem) {
                        $subcategory = array(
                            'id' => $listItem->getIndex(),
                            'title' => $listItem->getTitle(),
                            );
                        $subtitle = $listItem->getSubtitle();
                        if ($subtitle) {
                            $subcategory['subtitle'] = $subtitle;
                        }
                        $category['subcategories'][] = $subcategory;
                    }
                    $category['subcategoryCount'] = count($category['subcategories']);
                }
            }

            $categories[] = $category;
        }
        return $categories;
    }

    public function initializeForCommand() {
        
        switch ($this->command) {
            case 'campuses':
                // we need to move the list of campuses out of config 
                // and into a feed so we can have a parser do this work
                $numCampuses = $GLOBALS['siteConfig']->getVar('CAMPUS_COUNT');
                $campuses = array();
                for ($i = 0; $i < $numCampuses; $i++) {
                    $campusInfo = $GLOBALS['siteConfig']->getSection('campus-'.$i);
                    list($lat, $lon) = explode(',', $campusInfo['center']);
                    $address = array('display' => $campusInfo['address']);
                    $campus = array(
                        'id' => $campusInfo['id'],
                        'campus' => $campusInfo['id'],
                        'title' => $campusInfo['title'],
                        'lat' => $lat,
                        'lon' => $lon,
                        'address' => $address,
                        'description' => $campusInfo['description'],
                        );
                    $campuses[] = $campus;
                }
                
                $response = array(
                    'total' => $numCampuses,
                    'returned' => $numCampuses,
                    'displayField' => 'title',
                    'results' => $campuses,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);
            
                break;
            case 'categories':
                // TODO get category tree working
                if (!$this->feeds) {
                    $this->feeds = $this->loadFeedData();
                }

                $campusIndex = $this->getArg('campus'); // if this is null, fetch everything
                $categories = $this->getCategoriesForCampus($campusIndex);

                $this->setResponse($categories);
                $this->setResponseVersion(1);
            
                break;
            case 'places':
                $categoryPath = $this->getArg('category');
                if ($categoryPath) {
                    if (is_array($categoryPath)) {
                        $topCategory = array_shift($categoryPath);
                    } else {
                        $topCategory = $categoryPath;
                        $categoryPath = array();
                    }
                    $dataController = $this->getDataController($topCategory);
                    $listItems = $dataController->getListItems($categoryPath);
                    $places = array();
                    foreach ($listItems as $listItem) {
                        if ($listItem instanceof MapFeature) {
                            $places[] = arrayFromMapFeature($listItem);
                        }
                    }
                
                    $response = array(
                        'total' => count($places),
                        'returned' => count($places),
                        'displayField' => 'title',
                        'results' => $places,
                        );
                
                    $this->setResponse($response);
                    $this->setResponseVersion(1);
                }
                break;
            case 'search':
                $searchTerms = $this->getArg('q');
                if ($searchTerms) {

                    $mapSearchClass = $GLOBALS['siteConfig']->getVar('MAP_SEARCH_CLASS');
                    $mapSearch = new $mapSearchClass();
                    if (!$this->feeds)
                        $this->feeds = $this->loadFeedData();
                    $mapSearch->setFeedData($this->feeds);
        
                    $searchResults = $mapSearch->searchCampusMap($searchTerms);
        
                    $places = array();
                    foreach ($searchResults as $result) {
                        $places[] = arrayFromMapFeature($result);
                    }

                    $response = array(
                        'total' => count($places),
                        'returned' => count($places),
                        'displayField' => 'title',
                        'results' => $places,
                        );
                
                    $this->setResponse($response);
                    $this->setResponseVersion(1);
                }

                break;

            // ajax calls
            case 'staticImageURL':
                $baseURL = $this->getArg('baseURL');
                $mapClass = $this->getArg('mapClass');

                $pan = $this->getArg('pan');
                $zoom = $this->getArg('zoom');
                
                $mapController = MapImageController::factory($baseURL, $mapClass);
                if ($zoom) {
                    $level = $mapController->getLevelForZooming($zoom);
                    $mapController->setZoomLevel($level);
                }
                if ($pan) {
                    $center = $mapController->getCenterForPanning($pan);
                    $mapController->setCenter($center);
                }
                
                $url = $mapController->getImageURL();
                
                $this->setResponse($url);
                $this->setResponseVersion(1);
            
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}