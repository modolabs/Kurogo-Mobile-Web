<?php

// implemented by map categories, which have no geometry
interface MapListElement
{
    public function getTitle();
    public function getSubtitle();
}

// implemented by map data elements that can be displayed on a map
interface Placemark extends MapListElement
{
    public function getGeometry();
    public function getStyle();
    public function getAddress();
    public function getCategoryIds();
    public function getId();

    public function getField($fieldName);
    public function setField($fieldName, $value);
    public function getFields();
}

class MapBaseStyle implements MapStyle
{
    public function getStyleForTypeAndParam($type, $param) {
        return null;
    }
}
