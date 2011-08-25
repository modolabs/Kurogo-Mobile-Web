<?php

Kurogo::includePackage('Maps');

class MapAPIModule extends APIModule
{
    protected $id = 'map';
    protected $feeds = null;
    protected $vmin = 1;
    protected $vmax = 1;

    protected $feedGroup = null;
    protected $feedGroups = null;
    protected $numGroups;

    protected function shortArrayFromPlacemark(Placemark $placemark)
    {
        $result = array(
            'title' => $placemark->getTitle(),
            'subtitle' => $placemark->getSubtitle(),
            'id' => $placemark->getId(),
            'categories' => $placemark->getCategoryIds(),
            );

        $geometry = $placemark->getGeometry();
        if ($geometry) {
            $center = $geometry->getCenterCoordinate();

            $result['lat'] = $center['lat'];
            $result['lon'] = $center['lon'];
        }

        return $result;
    }

    protected function arrayFromPlacemark(Placemark $placemark)
    {
        $result = $this->shortArrayFromPlacemark($placemark);
        $result['fields'] = $placemark->getFields();
        $address = $placemark->getAddress();
        if ($address) {
            $result['address'] = $address;
        }

        $geometry = $placemark->getGeometry();
        if ($geometry) {
            if ($geometry instanceof MapPolygon) {
                $serializedGeometry = array();
                foreach ($geometry->getRings() as $ring) {
                    $serializedGeometry[] = $ring->getPoints();
                }

            } elseif ($geometry instanceof MapPolyline) {
                $serializedGeometry = $geometry->getPoints();

            } elseif ($geometry) {
                $serializedGeometry = $geometry->getCenterCoordinate();
            }
            $result['geometry'] = $serializedGeometry;
        }

        return $result;
    }

    // $category should implement MapListElement and MapFolder
    protected function arrayFromCategory(MapListElement $category)
    {
        $result = array(
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'subtitle' => $category->getSubtitle(),
            );

        return $result;
    }
    
    // functions duped from MapWebModule
    
    public function getFeedGroups() {
        if (!$this->feedGroups) {
            $this->feedGroups = $this->getModuleSections('feedgroups');
            $this->numGroups = count($this->feedGroups);
        }
        return $this->feedGroups;
    }
    
    // overrides function in Module.php
    protected function loadFeedData() {
        $this->feeds = array();
        $feedConfigFile = NULL;
        
        if ($this->feedGroup !== NULL) {
            if ($this->numGroups === 1) {
                $this->feedGroup = key($this->feedGroups);
            }
        }

        if ($this->numGroups === 0) {
            foreach ($this->getModuleSections('feeds') as $id => $feedData) {
                $feedId = mapIdForFeedData($feedData);
                $this->feeds[$feedId] = $feedData;
            }

        } elseif ($this->feedGroup !== NULL) {
            $configName = "feeds-{$this->feedGroup}";
            foreach ($this->getModuleSections($configName) as $id => $feedData) {
                $feedId = mapIdForFeedData($feedData);
                $this->feeds[$feedId] = $feedData;
            }

        } else {
            foreach ($this->getFeedGroups() as $groupID => $groupData) {
                $configName = "feeds-$groupID";
                foreach ($this->getModuleSections($configName) as $id => $feedData) {
                    $feedId = mapIdForFeedData($feedData);
                    $this->feeds[$feedId] = $feedData;
                }
            }
        }

        return $this->feeds;
    }

    private function getDataController($category=null) {
        $controller = null;
        if (!$category) {
            $category = $this->getArg('category');
        }

        if ($category) {

            $groups = array_keys($this->getFeedGroups());
            if (count($groups) <= 1) {
                $groups = array(null);
            }
            foreach ($groups as $groupID) {
                $this->feedGroup = $groupID;
                $feeds = $this->loadFeedData();
                if (isset($feeds[$category])) {
                    $feedData = $feeds[$category];
                    $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                    break;
                }
            }
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
        if ($mapSearch instanceof GoogleMapSearch && $mapSearch->isPlaces()) {
            // TODO notify client that logo is required
        }
        return $mapSearch;
    }

    // end of functions duped from mapwebmodule

    private function getCategoryReferences() {
        $path = $this->getArg('references', array());
        if ($path !== array()) {
            $path = explode(MAP_CATEGORY_DELIMITER, $path);
        }
        // remove empty strings from beginning of array
        while (count($path) && !strlen($path[0])) {
            array_shift($path);
        }
        return $path;
    }

    public function initializeForCommand() {

        switch ($this->command) {
            case 'index':
                $categories = array();
                $groups = $this->getFeedGroups();
                if ($groups) {
                    foreach ($groups as $id => &$groupData) {
                        if (isset($groupData['center'])) {
                            $latlon = filterLatLon($groupData['center']);
                            $groupData['lat'] = $latlon['lat'];
                            $groupData['lon'] = $latlon['lon'];
                        }
                        $groupData['id'] = $id;
                        $categories[] = $groupData;
                    }
                    $response = array('categories' => $categories);
                } else {
                    $feeds = $this->loadFeedData();
                    foreach ($feeds as $id => $feedData) {
                        $categories[] = array(
                            'title' => $feedData['TITLE'],
                            'subtitle' => $feedData['SUBTITLE'],
                            'id' => $id,
                            );
                    }
                }

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;
            
            case 'category':
                $this->loadFeedData();
                $category = $this->getArg('category');
                $groups = $this->getFeedGroups();

                if (isset($groups[$category])) {
                    $this->feedGroup = $category;
                    $groupData = $this->loadFeedData();
                    $categories = array();
                    foreach ($groupData as $id => $feed) {
                        if (!isset($feed['HIDDEN']) || !$feed['HIDDEN']) {
                            $category = array(
                                'id' => $id,
                                'title' => $feed['TITLE'],
                                );
                            if (isset($feed['SUBTITLE'])) {
                                $category['subtitle'] = $feed['SUBTITLE'];
                            }
                            $categories[] = $category;
                        }

                    }
                    $response = array('categories' => $categories);
                    $this->setResponse($response);
                    $this->setResponseVersion(1);

                } else {
                    $this->loadFeedData();

                    $currentCategory = null;
                    $drillPath = array();
                    if (isset($this->feeds[$category])) {
                        $currentCategory = $category;
                    } else {
                        // traces the parent categories that led the user to this category id
                        $references = $this->getCategoryReferences();
                        foreach ($references as $reference) {
                            if ($currentCategory) {
                                $drillPath[] = $reference;
                            } elseif (isset($this->feeds[$reference])) {
                                $currentCategory = $reference;
                            }
                        }
                        $drillPath[] = $category;
                    }
                    if ($currentCategory) {
                        $dataController = $this->getDataController($currentCategory);
                        if ($dataController) {
                            if ($drillPath) {
                                $dataController->addDisplayFilter('category', $drillPath);
                            }

                            $listItems = $dataController->getListItems();

                            $placemarks = array();
                            $categories = array();
                            foreach ($listItems as $listItem) {
                                if ($listItem instanceof Placemark) {
                                    $placemarks[] = $this->shortArrayFromPlacemark($listItem);

                                } else {
                                    $categories[] = $this->arrayFromCategory($listItem);
                                }
                            }

                            $response = array();
                            if ($placemarks) {
                                $response['placemarks'] = $placemarks;
                            }
                            if ($categories) {
                                $response['categories'] = $categories;
                            }

                            $this->setResponse($response);
                            $this->setResponseVersion(1);
                        }
                    }
                }

                break;

            case 'detail':

                $dataController = $this->getDataController();
                $drilldownPath = $this->getDrillDownPath();
                if ($drilldownPath) {
                    $dataController->addDisplayFilter('category', $drilldownPath);
                }
                if ($this->featureIndex !== null) {
                    $feature = $dataController->selectFeature($this->featureIndex);
                }

                $response = $this->arrayFromPlacemark($feature);

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            case 'search':
                $mapSearch = $this->getSearchClass($this->args);

                $searchType = $this->getArg('type');
                if ($searchType == 'nearby') {
                    $lat = $this->getArg('lat', 0);
                    $lon = $this->getArg('lon', 0);
                    if ($lat || $lon) {
                        $searchResults = $mapSearch->searchByProximity($center, 1000, 10);
                    }

                } else {
                    $searchTerms = $this->getArg('q');
                    if ($searchTerms) {
                        $searchResults = $mapSearch->searchCampusMap($searchTerms);
                    }
                }
                
                $places = array();
                foreach ($searchResults as $result) {
                    $places[] = $this->shortArrayFromPlacemark($result);
                }

                $response = array(
                    'total' => count($places),
                    'returned' => count($places),
                    'results' => $places,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;

            // ajax calls
            case 'projectPoint':

                $lat = $this->getArg('lat', 0);
                $lon = $this->getArg('lon', 0);

                $fromProj = $this->getArg('from', GEOGRAPHIC_PROJECTION);
                $toProj = $this->getArg('to', GEOGRAPHIC_PROJECTION);

                $projector = new MapProjector();
                $projector->setSrcProj($fromProj);
                $projector->setDstProj($toProj);
                $result = $projector->projectPoint(array('lat' => $lat, 'lon' => $lon));
                $this->setResponse($result);
                $this->setResponseVersion(1);

                break;

            case 'sortGroupsByDistance':
                
                $lat = $this->getArg('lat', 0);
                $lon = $this->getArg('lon', 0);

                $categories = array();

                if ($lat || $lon) {
                    foreach ($this->feedGroups as $id => $groupData) {
                        $categories[] = array(
                            'title' => $groupData['title'],
                            'id' => $id,
                            );
                        $center = filterLatLon($groupData['center']);
                        $distances[] = greatCircleDistance($lat, $lon, $center['lat'], $center['lon']);
                    }
                    array_multisort($distances, SORT_ASC, $categories);
                }

                $this->setResponse($categories);
                $this->setResponseVersion(1);

                break;

            case 'staticImageURL':

                $baseURL = $this->getArg('baseURL');
                $mapClass = $this->getArg('mapClass');

                $mapController = MapImageController::factory($mapClass, $baseURL);
                if (!$mapController->isStatic()) {
                    $error = new KurogoError(0, "staticImageURL must be used with a StaticMapImageController subclass");
                    $this->throwError($error);
                }

                $currentQuery = $this->getArg('query');
                $mapController->parseQuery($currentQuery);

                $overrides = $this->getArg('overrides');
                $mapController->parseQuery($overrides);

                $zoomDir = $this->getArg('zoom');
                if ($zoomDir == 1 || $zoomDir == 'in') {
                    $level = $mapController->getLevelForZooming('in');
                    $mapController->setZoomLevel($level);
                } elseif ($zoomDir == -1 || $zoomDir == 'out') {
                    $level = $mapController->getLevelForZooming('out');
                    $mapController->setZoomLevel($level);
                }

                $scrollDir = $this->getArg('scroll');
                if ($scrollDir) {
                    $center = $mapController->getCenterForPanning($scrollDir);
                    $mapController->setCenter($center);
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