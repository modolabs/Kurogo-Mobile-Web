<?php

class MapBasePolygon extends MapBasePoint implements MapPolygon {

    private $outerBoundary;
    private $innerBoundaries = array();

    public function __construct(Array $rings, $centroid=null) {
        $this->outerBoundary = new MapBasePolyline($rings[0]);
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
        $result = $this->innerBoundaries;
        array_unshift($result, $this->outerBoundary);
        return $result;
    }
}

