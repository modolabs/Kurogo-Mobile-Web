<?php

class KMLFolder extends KMLDocument implements MapListElement, MapFolder
{
    protected $items = array();
    protected $index;
    protected $category;

    protected $folders = array();
    protected $features = array();

    public function addItem(MapListElement $item) {
        if ($item instanceof Placemark) {
            $this->features[] = $item;
        } elseif ($item instanceof MapFolder) {
            $this->folders[] = $item;
        }
        $this->items[] = $item;
    }

    public function setId($index) {
        $this->index = $index;
    }
    
    // MapFolder interface

    public function getChildCategories()
    {
        return $this->folders;
    }

    public function getAllPlacemarks()
    {
        return $this->features;
    }
    
    public function getListItems() {
        return $this->items;
    }

    public function getProjection() {
        return null;
    }
    
    // MapListElement interface

    public function getSubtitle() {
        return $this->description;
    }

    public function getId() {
        return $this->index;
    }
}
