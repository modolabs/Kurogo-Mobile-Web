<?php

class BasePlacemark implements Placemark
{
    protected $id;
    protected $title;
    protected $address;
    protected $subtitle; // defaults to address if not present
    protected $geometry;
    protected $style = null;
    protected $fields = array();
    protected $categories = array();
    
    public function __construct(MapGeometry $geometry) {
        $this->geometry = $geometry;
        $this->style = new MapBaseStyle();
    }

    public function getId() {
        return $this->id;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setAddress($address) {
        $this->address = $address;
    }

    public function setGeometry(MapGeometry $geometry)
    {
        $this->geometry = $geometry;
    }
    
    // MapListElement interface
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getSubtitle() {
        if (isset($this->subtitle)) {
            return $this->subtitle;
        }
        return $this->address;
    }

    public function getCategoryIds() {
        return $this->categories;
    }

    public function addCategoryId($id)
    {
        if (!in_array($id, $this->categories)) {
            $this->categories[] = $id;
        }
    }

    // Placemark interface
    
    public function getGeometry() {
        return $this->geometry;
    }

    public function getFields()
    {
        return $this->fields;
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

    public function getStyle() {
        return $this->style;
    }

    public function setStyle(MapStyle $style)
    {
        $this->style = $style;
    }
    
    // setters that get used by MapWebModule when its detail page isn't called with a feature
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setSubtitle($subtitle) {
        $this->subtitle = $subtitle;
    }
}
