<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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

    public function searchByProximity($center, $tolerance=1000, $maxItems=0, $controller=null)
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

