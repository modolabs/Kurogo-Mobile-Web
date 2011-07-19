<?php

// TODO: support fetching of detail information via
// http://code.google.com/apis/maps/documentation/places/#PlaceDetails

class GooglePlacesParser extends DataParser implements MapDataParser
{
    private $items = array();

    public function parseData($data)
    {
        $decodedData = json_decode($data, true);
        if (!$decodedData['status'] == 'OK') {
            // handle error here
        }

        $results = $decodedData['results'];
        foreach ($results as $result) {
            $coord = $result['geometry']['location'];
            $centroid = new MapBasePoint($coord);
            $placemark = new BasePlacemark($centroid);
            $placemark->setTitle($result['name']);
            if (isset($decodedData['icon'])) {
                $placemark->setStyleForTypeAndParam(
                    MapStyle::POINT,
                    MapStyle::ICON,
                    $decodedData['icon']);
            }
            $this->items[] = $placemark;
        }
    }

    public function getProjection()
    {
        return null;
    }

    public function getListItems()
    {
        return $this->items;
    }

    public function getAllFeatures()
    {
        return $this->items;
    }

    public function getChildCategories()
    {
        return array();
    }
}
