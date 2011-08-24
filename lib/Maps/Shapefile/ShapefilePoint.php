<?php

class ShapefilePoint extends ShapefileGeometry
{
    public function getCenterCoordinate() {
        return $this->geomSpecs;
    }
}
