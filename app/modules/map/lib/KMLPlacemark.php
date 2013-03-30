<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KMLPlacemark extends XMLElement implements Placemark
{
    protected $name = 'Placemark';
    // placemarks have no guaranteed unique identifiers (id is optional)
    // so we assign this based on its position in the feed
    protected $index;
    protected $title; // use this for "name" element
    protected $description;
    protected $address;
    protected $snippet;
    protected $style;
    protected $geometry;
    protected $urlParams = array();
    protected $categories = array();

    private $fields = array();

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    $contents = array(
                        'title' => $this->getTitle(),
                        'subtitle' => $this->getSubTitle(),
                        'description' => $this->getDescription(),
                    );
                    return stringFilter($value, $contents);
                case 'min':
                    if (!isset($center)) {
                        $center = $this->getGeometry()->getCenterCoordinate();
                    }
                    if ($center['lat'] < $value['lat'] || $center['lon'] < $value['lon']) {
                        return false;
                    }
                    break;
                case 'max':
                    if (!isset($center)) {
                        $center = $this->getGeometry()->getCenterCoordinate();
                    }
                    if ($center['lat'] > $value['lat'] || $center['lon'] > $value['lon']) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;     
    }

    private static $elementMap = array(
        'NAME' => 'title',
        'DESCRIPTION' => 'description',
        'ADDRESS' => 'address',
        'SNIPPET' => 'snippet',
        );

    protected function elementMap() { return self::$elementMap; }
    
    public function __construct($name, $attribs)
    {
        $this->setAttribs($attribs);
    }

    // MapListElement interface

    public function getTitle() {
        if ($this->title === null) {
            return $this->getAttrib('ID');
        }
        return $this->title;
    }
    
    public function getSubtitle() {
        if (isset($this->address)) {
            return $this->address;
        }
        return $this->snippet;
    }
    
    public function getDescription($suppressFileds=null) {
        return $this->description;
    }
    
    public function getId() {
        return $this->index;
    }

    public function getFields() {
        return array('description' => $this->description);
    }

    // Placemark interface

    public function getURLParams() {
        $result = $this->urlParams;
        $result['featureindex'] = $this->getId();
        $categories = $this->getCategoryIds();
        $category = implode(MAP_CATEGORY_DELIMITER, $categories);
        if (strlen($category)) {
            $result['category'] = $category;
        }
        return $result;
    }

    public function setURLParam($name, $value) {
        $this->urlParams[$name] = $value;
    }

    public function getAddress() {
        return null;
    }

    public function getCategoryIds() {
        return $this->categories;
    }

    public function addCategoryId($id) {
        if ($id && !in_array($id, $this->categories)) {
            $this->categories[] = $id;
        }
    }

    public function getGeometry() {
        return $this->geometry;
    }

    public function getStyle() {
        return $this->style;
    }
    
    public function getField($fieldName) {
        if (isset($this->fields[$fieldName])) {
            return $this->fields[$fieldName];
        }
        return null;
    }
    
    public function setField($fieldName, $value) {
        $this->fields[$fieldName] = $value;
    }
    
    // setters

    public function setStyle(KMLStyle $style) {
        $this->style = $style;
    }
    
    // this is set to the sequence where the placemark is found in the KML file
    // because KML has no unique id's
    public function setId($index) {
        $this->index = $index;
    }

    // xml

    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        
        switch ($name)
        {
            case 'POINT':
            case 'LINESTRING':
            case 'LINEARRING':
            case 'POLYGON':
                $this->geometry = $element;
                break;
            case 'MULTIGEOMETRY':
            case 'MODEL':
            case 'GX:TRACK':
            case 'GX:MULTITRACK':
                throw new KurogoDataException("Geometry type $name not implemented yet");
                break;
            default:
                parent::addElement($element);
                break;
        }
    }

    public function serialize() {
        return serialize(
            array(
                'index' => $this->index,
                'title' => $this->title,
                'description' => $this->description,
                'address' => $this->address,
                'snippet' => $this->snippet,
                'urlParams' => serialize($this->urlParams),
                'categories' => serialize($this->categories),
                'fields' => serialize($this->fields),
                'style' => serialize($this->style),
                'geometry' => serialize($this->geometry),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->index = $data['index'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->address = $data['address'];
        $this->snippet = $data['snippet'];
        $this->urlParams = unserialize($data['urlParams']);
        $this->categories = unserialize($data['categories']);
        $this->fields = unserialize($data['fields']);
        $this->style = unserialize($data['style']);
        $this->geometry = unserialize($data['geometry']);
    }
}
