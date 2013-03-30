<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class GooglePlacesParser extends DataParser implements MapDataParser
{
    protected $id = 'Google Places';
    protected $title = 'Google Places';

    private $items = array();

    public function parseData($data)
    {
        $decodedData = json_decode($data, true);
        if (!$decodedData['status'] == 'OK') {
            // handle error here
        }

        if (isset($decodedData['results'])) {
            $results = $decodedData['results'];
        } elseif (isset($decodedData['result'])) {
            $results = array($decodedData['result']);
        }

        $position = 0; // geocode results have no id's

        foreach ($results as $result) {
            $coord = $result['geometry']['location'];
            $coord['lon'] = $coord['lng'];
            $centroid = new MapBasePoint($coord);
            $placemark = new BasePlacemark($centroid);
            if (isset($result['name'])) {
                $placemark->setTitle($result['name']);
            } elseif (isset($result['formatted_address'])) {
                $placemark->setTitle($result['formatted_address']);
            }
            if (isset($decodedData['icon'])) {
                $placemark->setStyleForTypeAndParam(
                    MapStyle::POINT,
                    MapStyle::ICON,
                    $decodedData['icon']);
            }
            if (isset($result['reference'])) {
                $placemark->setId($result['reference']);
            } else {
                $placemark->setId($position);
            }

            // fields returned by detail query
            // http://code.google.com/apis/maps/documentation/places/#PlaceDetails
            if (isset($result['vicinity'])) {
                $placemark->setField('vicinity', $result['vicinity']);
            }
            if (isset($result['formatted_phone_number'])) {
                $placemark->setField('phone', $result['formatted_phone_number']);
            }
            if (isset($result['url'])) {
                $placemark->setField('url', $result['url']);
            }
            if (isset($result['formatted_address'])) {
                $placemark->setAddress($result['formatted_address']);
            }
            $this->items[] = $placemark;

            $position++;
        }

        return $this->items;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function placemarks() {
        return $this->items;
    }

    public function categories() {
        return array();
    }

    public function getId() {
        return $this->id;
    }

    // everything below is legacy functions

    public function getProjection()
    {
        return null;
    }

    public function getListItems()
    {
        return $this->items;
    }

    public function getAllPlacemarks()
    {
        return $this->items;
    }

    public function getChildCategories()
    {
        return array();
    }
}
