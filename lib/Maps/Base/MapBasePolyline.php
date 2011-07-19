<?php

class MapBasePolyline extends MapBasePoint implements MapPolyline {

    private $points;
    private $centroid;

    public function __construct($points, $centroid=null) {
        $this->points = $points;
        if ($centroid) {
            $this->centroid = $centroid;
        }
    }

    public function getCenterCoordinate()
    {
        if (!isset($this->centroid)) {
            $lat = 0;
            $lon = 0;
            $n = count($this->points);
            foreach ($this->points as $coordinate) {
                $lat += $coordinate['lat'];
                $lon += $coordinate['lon'];
            }
            $this->centroid = array(
                'lat' => $lat / $n,
                'lon' => $lon / $n,
                );
        }
        return $this->centroid;
    }

    public function getPoints() {
        return $this->points;
    }
}

