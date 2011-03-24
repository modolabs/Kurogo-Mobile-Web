<?php

class MapSearch {

    protected $searchResults;
    protected $resultCount;
    protected $feeds;
    protected $feedGroup;
    
    public function __construct($feeds) {
        $this->setFeedData($feeds);
    }
    
    public function setFeedData($feeds) {
        $this->feeds = $feeds;
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

    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0) {
        $this->searchResults = array();

        $resultsByDistance = array();
        foreach ($this->feeds as $categoryID => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategory($categoryID);
            $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
            if ($controller->canSearch()) { // respect config settings
                $results = $controller->searchByProximity($center, $tolerance, $maxItems);
                // this runs a risk of eliminating search results that are the
                // same distance away (within 1 meter) in different feeds
                $resultsByDistance = array_merge($resultsByDistance, $results);
            }
        }

        ksort($resultsByDistance);

        if ($maxItems && count($resultsByDistance) > $maxItems) {
            array_splice($resultsByDistance, $maxItems);
        }

        $this->searchResults = array_values($resultsByDistance);
        return $this->searchResults;
    }

    public function searchCampusMap($query) {
        $this->searchResults = array();
    
    	foreach ($this->feeds as $id => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategory($id);
            $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
            
            if ($controller->canSearch()) {
                $results = $controller->search($query);
                $this->resultCount += count($results);
                $this->searchResults = array_merge($this->searchResults, $results);
            }
    	}
    	
    	return $this->searchResults;
    }
}



