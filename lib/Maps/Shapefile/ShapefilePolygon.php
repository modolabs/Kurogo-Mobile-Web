<?php

class ShapefilePolygon extends ShapefileGeometry implements MapPolygon
{
    private $rings;

    public function readGeometry($rings)
    {
        $this->rings = $rings;
    }

    public function getRings()
    {
        return $this->rings;
    }
}
