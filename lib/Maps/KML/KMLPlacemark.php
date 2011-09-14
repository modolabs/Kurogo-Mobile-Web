<?php

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
    //protected $category;
    protected $categories;

    private $fields = array();

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
    
    public function getId() {
        return $this->index;
    }

    public function getFields() {
        return array('description' => $this->description);
    }

    // Placemark interface

    public function getAddress() {
        return null;
    }

    public function getCategoryIds() {
        return $this->categories;
    }

    public function addCategoryId($id) {
        $this->categories[] = $id;
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
}
