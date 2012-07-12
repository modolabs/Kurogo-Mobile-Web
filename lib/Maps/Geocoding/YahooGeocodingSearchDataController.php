<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class YahooGeocodingSearchDataController extends GeocodingSearchDataController {

        // adding additional filters to the Geocoding service
    public function addCustomFilters($locationSearchTerms){

        // adding filters
        $this->addFilter('q', $locationSearchTerms);
        $this->addFilter('flags', 'J');
    }

    // this abstract function is not required
    public function getItem($id) {
        return;
    }
}
