<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapSearch extends DataRetriever {

    protected $searchResults;
    protected $resultCount;
    protected $feeds;
    protected $feedGroup;
    protected $searchParams;

    protected $searchMode;
    const SEARCH_MODE_TEXT = 0;
    const SEARCH_MODE_NEARBY = 1;
    
    public function __construct($feeds) {
        $this->setFeedData($feeds);
    }
    
    public function setFeedData($feeds) {
        $this->feeds = $feeds;
    }
    
    public function init($args) {
        parent::init($args);
        $this->setCacheGroup(get_class($this));
    }

    public function setFeedGroup($feedGroup) {
        $this->feedGroup = $feedGroup;
    }

    public function getSearchResults() {
        return $this->searchResults;
    }
    
    public function getResultCount() {
        return $this->resultCount;
    }

    public function retrieveResponse() {
        $response = $this->initResponse();
        switch ($this->searchMode) {
            case self::SEARCH_MODE_NEARBY:
                if (is_array($this->searchParams)) {
                    list($center, $tolerance, $maxItems, $dataSource) = $this->searchParams;
                    $response->setResponse(
                        $this->doSearchByProximity($center, $tolerance, $maxItems, $dataSource));
                }
                break;
            case self::SEARCH_MODE_TEXT:
            default:
                if (is_string($this->searchParams)) {
                    $response->setResponse(
                        $this->doSearchByText($this->searchParams));
                }
                break;
        }

        return $response;
    }

    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $dataSource=null) {
        $this->searchMode = self::SEARCH_MODE_NEARBY;
        $this->setContext('mode', 'nearby');
        $cacheKey = "c={$center['lat']},{$center['lon']}&t={$tolerance}&m={$maxItems}";
        if (isset($this->feedGroup) && strlen($this->feedGroup)) {
            $cacheKey .= "&g={$this->feedGroup}";
        }
        if (isset($dataSource)) {
            $id = $dataSource->getId();
            $cacheKey .= "&d={$id}";
        }
        $this->setCacheKey($cacheKey);
        $this->searchParams = array($center, $tolerance, $maxItems, $dataSource);

        $this->searchResults = $this->getData();
        if ($this->searchResults === null) {
            $this->searchResults = array();
        }
        $this->resultCount = count($this->searchResults);
        return $this->searchResults;
    }

    public function searchCampusMap($query) {
        $this->searchMode = self::SEARCH_MODE_TEXT;
        $this->setContext('mode', 'text');
        $cacheKey = "q={$query}";
        if (isset($this->feedGroup) && strlen($this->feedGroup)) {
            $cacheKey .= "&g={$this->feedGroup}";
        }
        $this->setCacheKey($cacheKey);
        $this->searchParams = $query;

        $this->searchResults = $this->getData();
        if ($this->searchResults === null) {
            $this->searchResults = array();
        }
        $this->resultCount = count($this->searchResults);
        return $this->searchResults;
    }

    protected function doSearchByProximity($center, $tolerance=1000, $maxItems=0, $controller=null) {

        $resultsByDistance = array();
        $controllers = array();
        if ($controller !== null) {
            $controllers[] = $controller;
        } else {
            foreach ($this->feeds as $categoryID => $feedData) {
                $feedData['group'] = $this->feedGroup;
                $controller = mapModelFromFeedData($feedData);
                if ($controller->canSearch()) { // respect config settings
                    $controllers[] = $controller;
                }
            }
        }

        // keep track of duplicate placemarks
        $unique = array();

        foreach ($controllers as $controller) {
            try {
                $results = $controller->searchByProximity($center, $tolerance, $maxItems);
                // merge arrays manually since keys are numeric
                foreach($results as $placemark) {
                    $toCenter = $placemark->getGeometry()->getCenterCoordinate();

                    // assume if placemarks have the same lat/lon
                    // and title then they are the same place
                    $testString = $toCenter['lat'].$toCenter['lon'].$placemark->getTitle();
                    if (in_array($testString, $unique)) {
                        continue;
                    }
                    $unique[] = $testString;

                    $distance = greatCircleDistance($center['lat'], $center['lon'], $toCenter['lat'], $toCenter['lon']);
                    $placemark->setField('distance', $distance);
                    // avoid distance collisions
                    if (isset($resultsByDistance[$distance])) {
                        $distance++;
                        while(isset($resultsByDistance[$distance])) {
                            $distance++;
                        }
                    }
                    $resultsByDistance[$distance] = $placemark;
                }

            } catch (KurogoDataServerException $e) {
                Kurogo::log(LOG_WARNING, 'encountered KurogoDataServerException for feed config: ' . print_r($feedData, true) . $e->getMessage(), 'maps');
            }
        }

        ksort($resultsByDistance);

        if ($maxItems && count($resultsByDistance) > $maxItems) {
            array_splice($resultsByDistance, $maxItems);
        }

        return array_values($resultsByDistance);
    }

    // sort by length of alias so the most specific (longer) ones are first    
    protected function sortAliases($a, $b) {
        $lenA = strlen($a);
        $lenB = strlen($b);
        if ($lenA == $lenB) {
            return 0;
        }
        return ($lenA > $lenB) ? -1 : 1;
    }

    protected function doSearchByText($query) {
        $allResults = array();
    	foreach ($this->feeds as $id => $feedData) {
    	
    	    //use aliases to remap search query
    	    $aliases = Kurogo::arrayVal($feedData, 'ALIASES', array());
    	    uksort($aliases, array($this,'sortAliases'));
            foreach ($aliases as $alias=>$map) {
                if (stripos($query, $alias)!== false) {
                    //use the alias's value for searching
                    $query = $map;
                    break;
                }
            }
        
    	    if ($this->feedGroup) {
                $feedData['group'] = $this->feedGroup;
            }
            $controller = mapModelFromFeedData($feedData);

            if ($controller->canSearch()) {
                try {
                    $results = $controller->search($query);
                    $allResults = array_merge($allResults, $results);
                } catch (KurogoDataServerException $e) {
                    Kurogo::log(LOG_WARNING,'encountered KurogoDataServerException for feed config: ' . print_r($feedData, true) .  $e->getMessage(), 'maps');
                }
            }
    	}

    	return $allResults;
    }
}



