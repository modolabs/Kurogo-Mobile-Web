<?php

class KMLLineString extends XMLElement implements MapPolyline
{
    private $coordinates = array();

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
}
