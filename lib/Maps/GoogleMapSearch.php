<?php

class GoogleMapSearch extends MapSearch {

    protected $dataModel;

    public function init($args) {
        parent::init($args);
        $this->dataModel = new GoogleGeoDataModel();
        $this->dataModel->init($args);
    }

    public function isPlaces()
    {
        return Kurogo::getOptionalSiteVar('USE_GOOGLE_PLACES', false, 'maps');
    }

    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $dataController=null)
    {
        $this->searchResults = $this->dataModel->searchByProximity($center, $tolerance, $maxItems);
        return $this->searchResults;
    }

    public function searchCampusMap($query)
    {
        $this->searchResults = $this->dataModel->search($query);
        return $this->searchResults;
    }
}

