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