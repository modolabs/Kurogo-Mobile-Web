<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KMLLineString extends XMLElement implements MapPolyline
{
    protected $coordinates = array();

    public function getCenterCoordinate()
    {
        $lat = 0;
        $lon = 0;
        $n = 0;
        foreach ($this->coordinates as $coordinate) {
            $lat += $coordinate['lat'];
            $lon += $coordinate['lon'];
            $n += 1;
        }
        return array(
            'lat' => $lat / $n,
            'lon' => $lon / $n,
            );
    }

    public function getPoints() {
        return $this->coordinates;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        switch ($name)
        {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#linestring
            case 'COORDINATES':
                foreach (preg_split('/\s/', trim($value)) as $line) {
                    $xyz = explode(',', trim($line));
                    if (count($xyz) >= 2) {
                        $this->coordinates[] = array(
                            'lon' => $xyz[0],
                            'lat' => $xyz[1],
                            'altitude' => isset($xyz[2]) ? $xyz[2] : null,
                            );
                    }
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
    }

    public function serialize() {
        return serialize(
            array(
                'coordinates' => serialize($this->coordinates),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->coordinates = unserialize($data['coordinates']);
    }
}
