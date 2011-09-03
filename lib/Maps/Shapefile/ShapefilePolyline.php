<?php

class ShapefilePolyline extends ShapefileGeometry implements MapPolyline
{
    // polyline means many lines but we currently treat the whole thing as a
    // single path
    private $mergedParts;

    public function readGeometry($parts) {
        $this->mergedParts = array();
        foreach ($parts as $part) {
            $this->mergedParts = array_merge($this->mergedParts, $part);
        }
    }

    public function getPoints()
    {
        return $this->mergedParts;
    }
}

