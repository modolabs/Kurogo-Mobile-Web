<?php

class ArcGISPolyline implements MapPolyline
{
    private $points;

    public function getPoints()
    {
        return $this->points;
    }

    public function __construct($geometry)
    {
        $this->points = $geometry;

        $totalLat = 0;
        $totalLon = 0;
        $n = count($this->points);
        foreach ($this->points as $point) {
            $totalLat += $point['lat'];
            $totalLon += $point['lon'];
        }
        $this->centerCoordinate = array('lat' => $totalLat / $n,
                                        'lon' => $totalLon / $n);
    }

    public function getCenterCoordinate()
    {
        return $this->centerCoordinate;
    }
}
