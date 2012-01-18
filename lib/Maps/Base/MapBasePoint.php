<?php

class MapBasePoint implements MapGeometry {

    protected $centroid;

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

    public function serialize() {
        return serialize(
            array(
                'centroid' => serialize($this->centroid),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->centroid = unserialize($data['centroid']);
    }
}

