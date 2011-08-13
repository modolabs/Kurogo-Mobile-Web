<?php

class GooglePlacesParser extends DataParser implements MapDataParser
{
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

        foreach ($results as $result) {
            $coord = $result['geometry']['location'];
            $coord['lon'] = $coord['lng'];
            $centroid = new MapBasePoint($coord);
            $placemark = new BasePlacemark($centroid);
            $placemark->setTitle($result['name']);
            if (isset($decodedData['icon'])) {
                $placemark->setStyleForTypeAndParam(
                    MapStyle::POINT,
                    MapStyle::ICON,
                    $decodedData['icon']);
            }
            $placemark->addCategoryId($this->dataController->getCategoryId());
            $placemark->setId($result['reference']);

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
        }

        return $this->items;
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
