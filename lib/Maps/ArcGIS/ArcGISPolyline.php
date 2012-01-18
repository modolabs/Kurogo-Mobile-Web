<?php

class ArcGISPolyline implements MapPolyline
{
    protected $points;
    protected $centerCoordinate;

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

    public function serialize() {
        return serialize(
            array(
                'points' => serialize($this->points),
                'centerCoordinate' => serialize($this->centerCoordinate),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->points = unserialize($data['points']);
        $this->centerCoordinate = unserialize($data['centerCoordinate']);
    }
}
