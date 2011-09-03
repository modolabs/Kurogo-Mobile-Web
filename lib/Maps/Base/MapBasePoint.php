<?php

class MapBasePoint implements MapGeometry {

    private $centroid;

    public function __construct($coordinates, $centroid=null) {
        $this->coordinates = $coordinates;
        if ($centroid) {
            $this->centroid = $centroid;
        } else if (isset($this->coordinates['lat'], $this->coordinates['lon'])) {
            $this->centroid = $this->coordinates;
        }
    }
    
    public function getCenterCoordinate() {
        return $this->centroid;
    }
}

