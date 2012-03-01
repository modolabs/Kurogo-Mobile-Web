<?php

class KMLPoint extends XMLElement implements MapGeometry
{
    private $coordinate;

    public function getCenterCoordinate()
    {
        return $this->coordinate;
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
       {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#point
            case 'COORDINATES':
                $xyz = explode(',', $value);
                $this->coordinate = array(
                    'lon' => trim($xyz[0]),
                    'lat' => trim($xyz[1]),
                    'altitude' => isset($xyz[2]) ? trim($xyz[2]) : null,
                    );
                break;
            default:
                parent::addElement($element);
                break;
        }
    }

    public function serialize() {
        return serialize(
            array(
                'coordinate' => serialize($this->coordinate),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->coordinate = unserialize($data['coordinate']);
    }
}
