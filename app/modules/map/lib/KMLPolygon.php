<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KMLPolygon extends XMLElement implements MapPolygon
{
    protected $outerBoundary;
    protected $innerBoundaries = array();

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

    public function serialize() {
        return serialize(
            array(
                'outerBoundary' => serialize($this->outerBoundary),
                'innerBoundaries' => serialize($this->innerBoundaries),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->outerBoundary = unserialize($data['outerBoundary']);
        $this->innerBoundaries = unserialize($data['innerBoundaries']);
    }
}
