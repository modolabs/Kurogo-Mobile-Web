<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
abstract class GeocodingSearchDataController extends DataController {

      // adding additional filters to the Geocoding service
    abstract function addCustomFilters($locationSearchTerms);
}
?>
