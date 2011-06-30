<?php

// implemented by map categories, which have no geometry
interface MapListElement
{
    const DESCRIPTION_TEXT = 0; // used by the majority of map data elements
    const DESCRIPTION_LIST = 1; // used by ArcGIS map features, which store attributes as an array.
                                // if we support backends like PostGIS, ESRI shapefiles/geodatabases
                                // etc. we will start seeing more of these guys.

    public function getTitle();
    public function getSubtitle();
    //public function getIndex();
    //public function getCategory();
}

// implemented by map data elements that can be displayed on a map
interface Placemark extends MapListElement
{
    public function getGeometry();
    public function getDescription();
    public function getDescriptionType();
    public function getStyle();
    public function getAddress();
    public function getCategoryIds();

    public function getField($fieldName);
    public function setField($fieldName, $value);
}

class MapBaseStyle implements MapStyle
{
    public function getStyleForTypeAndParam($type, $param) {
        return null;
    }
}
