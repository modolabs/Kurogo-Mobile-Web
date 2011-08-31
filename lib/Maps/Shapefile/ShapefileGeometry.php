<?php

class ShapefileGeometry extends BasePlacemark implements MapGeometry
{
    protected $geomSpecs;
    protected $bbox;
    protected $category;
    protected $titleField = null;
    protected $subtitleField = null;

    // parent requires a geometry parameter
    // we don't because we are geometry
    public function __construct() { }

    // TODO: these are placeholder implementations of
    // getTitle and getSubtitle.  the title and subtitle
    // fields should be config values.  guessing fields
    // happens to work great for this boston feed but it
    // will not in general.

    public function getTitle() {
        if ($this->titleField && isset($this->fields[$this->titleField])) {
            return $this->fields[$this->titleField];
        }
        // otherwise pick a random field
        if (count($this->fields) > 1) {
            $array = array_values($this->fields);
            return next($array);
        }
        return null;
    }

    public function getSubtitle() {
        if ($this->subtitleField && isset($this->fields[$this->subtitleField])) {
            return $this->fields[$this->subtitleField];
        }
        return null;
    }

    public function setTitleField($field) {
        $this->titleField = $field;
    }

    public function setSubtitleField($field) {
        $this->subtitleField = $field;
    }

    public function setFields($fields) {
        $this->fields = $fields;
    }

    public function readGeometry($geomSpecs) {
        $this->geomSpecs = $geomSpecs;
    }

    public function getGeometry() {
        if (isset($this->geometry) && $this->geometry instanceof MapGeometry) {
            return $this->geometry;
        }
        return $this;
    }

    public function setBBox($bbox) {
        $this->bbox = $bbox;
    }

    public function getCenterCoordinate() {
        if (isset($this->bbox)) {
            $point = array(
                'lat' => ($this->bbox['ymin'] + $this->bbox['ymax']) / 2,
                'lon' => ($this->bbox['xmin'] + $this->bbox['xmax']) / 2,
                );
            return $point;
        }

        return null;
    }
}
