<?php

// implemented by map data elements that can be displayed on a map
interface Placemark extends MapListElement, Serializable
{
    public function getGeometry();
    public function getStyle();
    public function getAddress();

    public function getURLParams();
    public function setURLParam($name, $value); // set by feeds when children are created

    // we don't currently have placemarks that belong in multiple categories
    // but there are some kml-like formats that do 
    public function getCategoryIds();
    public function addCategoryId($id);

    public function getField($fieldName);
    public function setField($fieldName, $value);
    public function getFields();
}

