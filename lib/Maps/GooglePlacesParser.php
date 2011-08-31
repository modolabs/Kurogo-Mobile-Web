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

        $position = 0; // geocode results have no id's

        foreach ($results as $result) {
            $coord = $result['geometry']['location'];
            $coord['lon'] = $coord['lng'];
            $centroid = new MapBasePoint($coord);
            $placemark = new BasePlacemark($centroid);
            if (isset($decodedData['name'])) {
                $placemark->setTitle($result['name']);
            } elseif (isset($result['formatted_address'])) {
                $placemark->setTitle($result['formatted_address']);
            } else {
                $placemark->setTitle($this->dataController->getSearchText());
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
            $placemark->addCategoryId($this->dataController->getCategoryId());

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
        return 'Google Places';
    }

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
