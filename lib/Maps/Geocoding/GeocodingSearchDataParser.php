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

abstract class GeocodingSearchDataParser extends DataParser{

     /* This function must be implemented in the subclassed geocoding-service data parser.
      *
      * $data is provided by the parseData($data) function. It has the data provided by the Geocoding service
      *
      * The returning array ($reponse) must be of the following type:
      *
      * $reponse =
      *
      * {   'locationsCount'    => number of results returned
      *     'errorCode'         => 0 if no error, and the error number returned by the service otherwise
      *     'errorMsg'          => error Message returned by the geocoding service, if any
      *     'locationsArray'    => an array of locations, empty if locationsCount is zero.
      *                            Each locationArray element must at least contain 'latitude' and 'longitude' values.
      *                            All other values (e.g. countryCode) are optional
      * }
      */
    abstract function parseGeocodingData($data);
}
