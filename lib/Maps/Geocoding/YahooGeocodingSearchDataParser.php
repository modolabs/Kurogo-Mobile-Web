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
class YahooGeocodingSearchDataParser extends GeocodingSearchDataParser{

    protected $assoc=true;

    public function parseData($data) {

        return $this->parseGeocodingData($data);

    }

    // Parsing Yahoo! Geocoding service data
    public function parseGeocodingData($data){

        $resultSet = json_decode($data, $this->assoc);

        $count = $resultSet['ResultSet']['Found'];
        $error = $resultSet['ResultSet']['Error'];
        $errorMsg = $resultSet['ResultSet']['ErrorMessage'];

        $locationArray = array();
        if (($count > 0) && ($error == 0)){
            $resultArray = $resultSet['ResultSet']['Results'];
            $resultsToReturn = array();
            $resultsToReturn['locationsCount'] = $count;

            foreach ($resultArray as $result) {

                // removing unnecessary fields from the results
                unset($result['quality'], $result['offsetlat'],
                        $result['offsetlon'], $result['radius'],
                        $result['hash'], $result['woeid'],
                        $result['woetype'], $result['unittype'],
                        $result['street'], $result['countycode'],
                        $result['county']);

                $locationArray[] = $result;
            }
            $resultsToReturn['errorCode'] = $error;
            $resultsToReturn['errorMessage'] = "";

        }

        else if (strlen($error) > 0){
            $resultsToReturn['errorCode'] = $error;
            $resultsToReturn['errorMessage'] = $errorMsg;
        }


        $resultsToReturn['locationsArray'] = $locationArray;

        return $resultsToReturn;
    }
}
