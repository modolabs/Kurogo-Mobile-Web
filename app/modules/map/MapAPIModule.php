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

    protected $currentFeedData;

    protected $dataModel;
    protected $mapProjector;

    // reimplements a subset of MapWebModule::linkForItem
    protected function urlForPlacemark(Placemark $placemark)
    {
        $urlArgs = $placemark->getURLParams();

        // mimic getMergedConfigData in MapWebModule
        $categoryArg = isset($urlArgs['category']) ? $urlArgs['category'] : null;
        $categories = explode(MAP_CATEGORY_DELIMITER, $categoryArg);
        $category = current($categories);
        if ($category) {
            $urlArgs['feed'] = $category;
        }

        $configData = $this->getDataForGroup($this->feedGroup);

        // allow individual feeds to override group value
        $feedData = $this->getCurrentFeed($category);
        if ($feedData) {
            foreach ($feedData as $key => $value) {
                $configData[$key] = $value;
            }
        }

        // the device needs to be compliant to use the APIModule
        list($class, $static) = MapImageController::basemapClassForDevice(
            new MapDevice('compliant', 'computer'),
            $configData);
        
        if ($static) {
            $page = $this->numGroups > 1 ? 'campus' : 'index';
        } else {
            $page = 'detail';
        }

        return rtrim(FULL_URL_PREFIX, '/'). '/'. $this->configModule.'/'.$page.'?'.http_build_query($urlArgs);
    }

    protected function shortArrayFromPlacemark(Placemark $placemark)
    {
        $result = array(
            'title' => $placemark->getTitle(),
            'subtitle' => $placemark->getSubtitle(),
            'id' => $placemark->getId(),
            'categories' => $placemark->getCategoryIds(),
            'url' => $this->urlForPlacemark($placemark),
            );

        $geometry = $placemark->getGeometry();
        if ($geometry) {
            $center = $geometry->getCenterCoordinate();
            if (isset($this->mapProjector)) {
                $center = $this->mapProjector->projectPoint($center);
            }

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
    
    private function getDataForGroup($group) {
        $this->getFeedGroups();
        return isset($this->feedGroups[$group]) ? $this->feedGroups[$group] : null;
    }
    
    public function getFeedGroups() {
        if (!$this->feedGroups) {
            $this->feedGroups = $this->getModuleSections('feedgroups');
            $this->numGroups = count($this->feedGroups);
        }
        return $this->feedGroups;
    }
    
    // overrides function in Module.php
    protected function loadFeedData() {
        $this->getFeedGroups();

        $this->feeds = array();
        $feedConfigFile = NULL;
        
        if ($this->feedGroup === NULL) {
            if ($this->getArg('group')) {
                $this->feedGroup = $this->getArg('group');
            } elseif ($this->numGroups === 1) {
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

    private function getDataModel($feedId=null)
    {
        if (!$this->feeds) {
            $this->loadFeedData();
        }

        // re-instantiate DataModel if a different feed is requested.
        if ($this->dataModel && $feedId !== $this->dataModel->getFeedId()) {
            $this->dataModel = null;
        }

        $categoryId = $this->getArg('references');

        if ($this->dataModel === null) {
            if ($feedId === null) {
                $testFeedId = $this->getArg('category');
                if (isset($this->feeds[$feedId])) {
                    $feedId = $testFeedId;
                } else {
                    foreach (explode(MAP_CATEGORY_DELIMITER, $categoryId) as $testId) {
                        if (isset($this->feeds[$testId])) {
                            $feedId = $testId;
                            break;
                        }
                    }
                }
            }

            $feedData = $this->getCurrentFeed($feedId);
            $this->dataModel = mapModelFromFeedData($feedData);
        }

        if (isset($categoryId)) {
            $this->dataModel->findCategory($categoryId);
        }

        return $this->dataModel;
    }

    private function getCurrentFeed($category=null)
    {
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
                    $this->currentFeedData = $feeds[$category];
                    break;
                }
            }
        }
        return $this->currentFeedData;
    }

    protected function getSearchClass($options=array()) {
        $mapSearchClass = $this->getOptionalModuleVar('MAP_SEARCH_CLASS', 'MapSearch');
        if (isset($options['external']) && $options['external']) {
            // use the same search class by default
            $mapSearchClass = $this->getOptionalModuleVar('MAP_EXTERNAL_SEARCH_CLASS', $mapSearchClass);
        }
        if (!$this->feeds) {
            $this->feeds = $this->loadFeedData();
        }
        $mapSearch = new $mapSearchClass($this->feeds);
        $mapSearch->setFeedGroup($this->feedGroup);
        $mapSearch->init($this->getDataForGroup($this->feedGroup));
        return $mapSearch;
    }

    // end of functions duped from mapwebmodule

    /*
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
    */

    protected function getGeometryType(MapGeometry $geometry) {
        if ($geometry instanceof MapPolygon) {
            return 'polygon';
        }
        if ($geometry instanceof MapPolyline) {
            return 'polyline';
        }
        return 'point';
    }

    protected function formatGeometry(MapGeometry $geometry) {
        $result = array();
        if ($geometry instanceof MapPolygon) {
            foreach ($geometry->getRings() as $aRing) {
                $result[] = $aRing->getPoints();
            }

        } elseif ($geometry instanceof MapPolyline) {
            $result = $geometry->getPoints();

        } else {
            $result = $geometry->getCenterCoordinate();
        }
        return $result;
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

    public function initializeForCommand() {

        if (($projection = $this->getArg('projection'))) {
            $this->mapProjector = new MapProjector();
            $this->mapProjector->setDstProj($projection);
        }

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
                $categoryId = $this->getArg('category');
                $groups = $this->getFeedGroups();

                if (isset($groups[$categoryId])) {
                    $this->feedGroup = $categoryId;
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
                    $dataController = $this->getDataModel();

                    if ($dataController) {
                        //if ($categoryId) {
                        //    $category = $dataController->findCategory($categoryId);
                        //    $placemarks = $category->placemarks();
                        //    $categories = $category->categories();
                        //} else {
                            $placemarks = $dataController->placemarks();
                            $categories = $dataController->categories();
                        //}

                        $response = array();
                        if ($placemarks) {
                            $response['placemarks'] = array();
                            foreach ($placemarks as $placemark) {
                                $response['placemarks'][] = $this->arrayFromPlacemark($placemark);
                            }
                        }
                        if ($categories) {
                            $response['categories'] = array();
                            foreach ($categories as $aCategory) {
                                $response['categories'][] = $this->arrayFromCategory($aCategory);
                            }
                        }

                        $this->setResponse($response);
                        $this->setResponseVersion(1);
                    } else {
                        $error = new KurogoError("Could not find data source for requested category");
                        $this->throwError($error);
                    }
                }

                break;

            case 'detail':

                $dataController = $this->getDataModel();
                $placemarkId = $this->getArg('id', null);
                if ($dataController && $placemarkId !== null) {
                    $placemarks = $dataController->selectPlacemark($placemarkId);
                    $placemark = current($placemarks);

                    $fields = $placemark->getFields();
                    $geometry = $placemark->getGeometry();

                    $response = array(
                        'id'       => $placemarkId,
                        'title'    => $placemark->getTitle(),
                        'subtitle' => $placemark->getSubtitle(),
                        'address'  => $placemark->getAddress(),
                        'details'  => $placemark->getFields(),
                    );

                    if ($geometry) {
                        $center = $geometry->getCenterCoordinate();
                        $response['lat'] = $center['lat'];
                        $response['lon'] = $center['lon'];
                        $response['geometryType'] = $this->getGeometryType($geometry);
                        $response['geometry'] = $this->formatGeometry($geometry);
                    }

                    $this->setResponse($response);                                                              
                    $this->setResponseVersion(1);                                                               
                }

                break;

            case 'search':
                $mapSearch = $this->getSearchClass($this->args);

                $lat = $this->getArg('lat', 0);
                $lon = $this->getArg('lon', 0);
                if ($lat || $lon) {
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

                    $searchResults = $mapSearch->searchByProximity(
                        array('lat' => $lat, 'lon' => $lon),
                        1000, 10);

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

                $showDistances = $this->getOptionalModuleVar('SHOW_DISTANCES', true);

                if ($lat || $lon) {
                    foreach ($this->getFeedGroups() as $id => $groupData) {
                        $center = filterLatLon($groupData['center']);
                        $distance = greatCircleDistance($lat, $lon, $center['lat'], $center['lon']);
                        $category = array(
                            'title' => $groupData['title'],
                            'id' => $id,
                            );
                        if ($showDistances && ($displayText = $this->displayTextFromMeters($distance))) {
                            $category['distance'] = $displayText;
                        }
                        $categories[] = $category;
                        $distances[] = $distance;
                    }
                    array_multisort($distances, SORT_ASC, $categories);
                }

                $this->setResponse($categories);
                $this->setResponseVersion(1);

                break;

            case 'staticImageURL':

                $params = array(
                    'STATIC_MAP_BASE_URL' => $this->getArg('baseURL'),
                    'STATIC_MAP_CLASS' => $this->getArg('mapClass'),
                    );
                
                $dc = Kurogo::deviceClassifier();
                $mapDevice = new MapDevice($dc->getPagetype(), $dc->getPlatform());

                $mapController = MapImageController::factory($params, $mapDevice);
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
            {
                // TODO: this is not fully implemented. do not use this API.

                includePackage('Maps', 'Geocoding');

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
            }
            default:
                $this->invalidCommand();
                break;
        }
    }
}
