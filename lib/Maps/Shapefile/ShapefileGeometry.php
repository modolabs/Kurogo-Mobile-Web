<?php

class ShapefileGeometry extends BasePlacemark implements MapGeometry
{
    protected $geomSpecs;
    protected $bbox;
    protected $properties;
    protected $category;

    // parent requires a geometry parameter
    // we don't because we are geometry
    public function __construct() { }

    // TODO: these are placeholder implementations of
    // getTitle and getSubtitle.  the title and subtitle
    // fields should be config values.  guessing fields
    // happens to work great for this boston feed but it
    // will not in general.

    public function getTitle() {
        if (count($this->properties) > 1) {
            $array = array_values($this->properties);
            return next($array);
        }
        return null;
    }

    public function getSubtitle() {
        if (count($this->properties) > 2) {
            $array = array_values($this->properties);
            next($array);
            return next($array);
        }
        return null;
    }

    public function setFields($properties) {
        $this->properties = $properties;
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
