<?php

class GoogleMapSearch extends MapSearch {

    protected $feedData = array(
        'CONTROLLER_CLASS' => 'GoogleGeoDataController',
        );

    public function isPlaces()
    {
        return Kurogo::getOptionalSiteVar('USE_GOOGLE_PLACES', false, 'maps');
    }

    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $dataController=null)
    {
        $controller = MapDataController::factory($this->feedData['CONTROLLER_CLASS'], $this->feedData);
        $this->searchResults = $controller->searchByProximity($center, $tolerance, $maxItems);
        return $this->searchResults;
    }

    public function searchCampusMap($query)
    {
        $controller = MapDataController::factory($this->feedData['CONTROLLER_CLASS'], $this->feedData);
        $this->searchResults = $controller->search($query);
        return $this->searchResults;
    }
}

