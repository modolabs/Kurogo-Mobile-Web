<?php

includePackage('Maps');

class MapAPIModule extends APIModule
{
    protected $id = 'map';
    protected $feeds = null;
    protected $vmin = 1;
    protected $vmax = 1;

    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $numGroups = 1;

    protected function arrayFromMapFeature(MapFeature $feature) {
        $category = $feature->getCategory();
        if (!is_array($category)) {
            $category = explode(MAP_CATEGORY_DELIMITER, $category);
        }
        $result = array(
            'title' => $feature->getTitle(),
            'subtitle' => $feature->getSubtitle(),
            'id' => $feature->getIndex(),
            'category' => $category,
            'description' => $feature->getDescription(),
            );

        $geometry = $feature->getGeometry();
        if ($geometry) {
            $center = $geometry->getCenterCoordinate();
            if ($geometry instanceof MapPolygon) {
                $serializedGeometry = $geometry->getRings();
            } elseif ($geometry instanceof MapPolyline) {
                $serializedGeometry = $geometry->getPoints();
            } elseif ($geometry) {
                $serializedGeometry = $geometry->getCenterCoordinate();
            }
            $result['geometry'] = $serializedGeometry;
            $result['lat'] = $center['lat'];
            $result['lon'] = $center['lon'];
        }

        return $result;
    }
    
    // functions duped from MapWebModule

    protected function getDataController($categoryPath, &$listItemPath) {
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

    protected function getFeedGroups() {
        return $this->getModuleSections('feedgroups');
    }

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

    protected function getCategoriesAsArray() {
        $category = $this->getArg('category', null);
        if ($category !== null) {
            return explode(MAP_CATEGORY_DELIMITER, $category);
        }
        return array();
    }

    protected function initializeForSearch() {
        $this->feedGroup = $this->getArg('group', null);
        if ($this->feedGroup !== NULL && !isset($this->feedGroups[$this->feedGroup])) {
            $this->feedGroup = NULL;
        }

        $mapSearchClass = $this->getOptionalModuleVar('MAP_SEARCH_CLASS', 'MapSearch');
        if (!$this->feeds)
            $this->feeds = $this->loadFeedData();
        $mapSearch = new $mapSearchClass($this->feeds);

        $searchType = $this->getArg('type', '');
        switch ($searchType) {
            case 'detail':
                $identifier = $this->getArg('identifier');
                if ($identifier) {
                    $feature = $this->dataController->getFeature($identifier, $categoryPath);

                    $response = array(
                        'total' => 1,
                        'returned' => 1,
                        'displayField' => 'title',
                        'results' => array($this->arrayFromMapFeature($feature)),
                        );

                    $this->setResponse($response);
                    $this->setResponseVersion(1);

                } else {
                    // TODO return a more informative error
                    $this->invalidCommand();
                }

                break;

            case 'nearby':
                $lat = $this->getArg('lat', 0);
                $lon = $this->getArg('lon', 0);

                $center = array('lat' => $lat, 'lon' => $lon);
                $searchResults = $mapSearch->searchByProximity($center, 1000, 10);
                
                $places = array();
                foreach ($searchResults as $result) {
                    $places[] = $this->arrayFromMapFeature($result);
                }

                $response = array(
                    'total' => count($places),
                    'returned' => count($places),
                    'displayField' => 'title',
                    'results' => $places,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            default:
                $searchTerms = $this->getArg('q');
                if ($searchTerms) {

                    $searchResults = $mapSearch->searchCampusMap($searchTerms);

                    $places = array();
                    foreach ($searchResults as $result) {
                        $places[] = $this->arrayFromMapFeature($result);
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
        }
    }

    private function titleSort($a, $b) {
      return strnatcasecmp($a['title'], $b['title']);
    }

    // end of functions duped from mapwebmodule

    public function initializeForCommand() {

        $this->feedGroups = $this->getFeedGroups();
        $this->numGroups = count($this->feedGroups);

        switch ($this->command) {
            case 'categories':
                $this->feedGroup = $this->getArg('group', null);
                if ($this->feedGroup !== NULL && !isset($this->feedGroups[$this->feedGroup])) {
                    $this->feedGroup = NULL;
                }

                $categories = array();
                $this->feeds = $this->loadFeedData();
                foreach ($this->feeds as $id => $feedData) {
                    if (isset($feedData['HIDDEN']) && $feedData['HIDDEN']) continue;
                    $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                    $controller->setCategory($id);
                    $category = array(
                        'id' => $controller->getCategory(),
                        'title' => self::argVal($feedData, 'TITLE', $controller->getTitle()),
                        );
                    $category['subcategories'] = $controller->getAllCategoryNodes();
                    $categories[] = $category;
                }
                usort($categories, array(get_class($this), 'titleSort'));

                $this->setResponse($categories);
                $this->setResponseVersion(1);
            
                break;
            case 'places':
                $categoryPath = $this->getCategoriesAsArray();
                if ($categoryPath) {
                    $dataController = $this->getDataController($categoryPath, $listItemPath);
                    $listItems = $dataController->getListItems($listItemPath);
                    $places = array();
                    foreach ($listItems as $listItem) {
                        if ($listItem instanceof MapFeature) {
                            $aPlace = $this->arrayFromMapFeature($listItem);
                            $aPlace['category'] = $categoryPath;
                            $places[] = $aPlace;
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
                $this->initializeForSearch();

                break;

            // ajax calls
            case 'staticImageURL':
                $baseURL = $this->getArg('baseURL');
                $mapClass = $this->getArg('mapClass');
                $mapController = MapImageController::factory($mapClass, $baseURL);
                
                $projection = $this->getArg('projection');
                if ($projection) {
                    $mapController->setMapProjection($projection);
                }
                
                $width = $this->getArg('width');
                if ($width) {
                    $mapController->setImageWidth($width);
                }

                $height = $this->getArg('height');
                if ($height) {
                    $mapController->setImageHeight($height);
                }

                $bbox = $this->getArg('bbox', null);
                $lat = $this->getArg('lat');
                $lon = $this->getArg('lon');
                $zoom = $this->getArg('zoom');

                if ($bbox) {
                    $mapController->setBoundingBox($bbox);

                } else if ($lat && $lon && $zoom !== null) {
                    $mapController->setZoomLevel($zoom);
                    $mapController->setCenter(array('lat' => $lat, 'lon' => $lon));
                }
                
                $url = $mapController->getImageURL();
                
                $this->setResponse($url);
                $this->setResponseVersion(1);
            
                break;

                case 'geocode':
                    $locationSearchTerms = $this->getArg('q');
                    
                    $geocodingDataControllerClass = $this->getOptionalModuleVar('GEOCODING_DATA_CONTROLLER_CLASS');
                    $geocodingDataParserClass = $this->getOptionalModuleVar('GEOCODING_DATA_PARSER_CLASS');
                    $geocoding_base_url = $this->getOptionalModuleVar('GEOCODING_BASE_URL');

                    $arguments = array('BASE_URL' => $geocoding_base_url,
                                  'CACHE_LIFETIME' => 86400,
                                  'PARSER_CLASS' => $geocodingDataParserClass);

                    $controller = DataController::factory($geocodingDataControllerClass, $arguments);
                    $controller->addCustomFilters($locationSearchTerms);
                    $response = $controller->getParsedData();

                    // checking for Geocoding service error
                    if ($response['errorCode'] == 0) {

                        unset($response['errorCode']);
                        unset($response['errorMessage']);
                        $this->setResponse($response);
                        $this->setResponseVersion(1);
                    }
                    else {
                        $kurogoError = new KurogoError($response['errorCode'], "Geocoding service Erroe", $response['errorMessage']);
                        $this->setResponseError($kurogoError);
                        $this->setResponseVersion(1);
                    }
                    break;
                    
            default:
                $this->invalidCommand();
                break;
        }
    }
}