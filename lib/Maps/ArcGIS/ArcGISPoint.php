<?php

class ArcGISPoint implements MapGeometry
{
    private $x;
    private $y;

    public function __construct($geometry)
    {
        $this->x = $geometry['x'];
        $this->y = $geometry['y'];
    }
    
    public function getCenterCoordinate()
    {
        return array('lat' => $this->y, 'lon' => $this->x);
    }
}
