<?php

interface MapGeometry
{
    // must return an array of the form {'lat' => 2.7182, 'lon' => -3.1415}
    public function getCenterCoordinate();
}

interface MapPolyline extends MapGeometry
{
    public function getPoints();
}

interface MapPolygon extends MapGeometry
{
    public function getRings();
}

class MapBasePoint implements MapGeometry {

    private $centroid;

    public function __construct($coordinates, $centroid=null) {
        $this->coordinates = $coordinates;
        if ($centroid) {
            $this->centroid = $centroid;
        } else if (count($this->coordinates) == 2) {
            $this->centroid = $this->coordinates;
        }
    }
    
    public function getCenterCoordinate() {
        return $this->centroid;
    }
}

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

class MapBasePolygon extends MapBasePoint implements MapPolygon {

    private $outerBoundary;
    private $innerBoundaries = array();

    public function __construct(Array $rings) {
        $this->outerBoundary = new BasePolyline($rings[0]);
        if (count($rings) > 1) {
            for ($i = 1; $i < count($rings); $i++) {
                $this->innerBoundaries[] = new MapBasePolyline($rings[$i]);
            }
        }
        if ($centroid) {
            $this->centroid = $centroid;
        }
    }

    public function getCenterCoordinate()
    {
        return $this->outerBoundary->getCenterCoordinate();
    }

    public function getRings()
    {
        $outerRing = $this->outerBoundary->getPoints();
        $result = array($outerRing);
        if (isset($this->innerBoundaries) && count($this->innerBoundaries)) {
            foreach ($this->innerBoundaries as $boundary) {
                $result[] = $boundary->getPoints();
            }
        }
        return $result;
    }
}
