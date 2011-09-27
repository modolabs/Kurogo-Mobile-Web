<?php

class KMLPolygon extends XMLElement implements MapPolygon
{
    private $outerBoundary;
    private $innerBoundaries = array();

    public function getCenterCoordinate()
    {
        return $this->outerBoundary->getCenterCoordinate();
    }

    public function getRings()
    {
        return array_merge(array($this->outerBoundary), $this->innerBoundaries);
    }

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            // more tags see
            // http://code.google.com/apis/kml/documentation/kmlreference.html#polygon
            case 'OUTERBOUNDARYIS':
                $this->outerBoundary = $element->getChildElement('LINEARRING');
                break;
            case 'INNERBOUNDARYIS':
                $this->innerBoundaries[] = $element->getChildElement('LINEARRING');
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
}
