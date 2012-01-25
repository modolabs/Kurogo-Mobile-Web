<?php
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
?>
