<?php

// implemented by map data elements that can be displayed on a map
interface Placemark extends MapListElement, KurogoObject
{
    public function getGeometry();
    public function getStyle();
    public function getAddress();

    // we don't currently have placemarks that belong in multiple categories
    // but there are some kml-like formats that do 
    public function getCategoryIds();
    public function addCategoryId($id);

    public function getId();

    public function getField($fieldName);
    public function setField($fieldName, $value);
    public function getFields();
}

