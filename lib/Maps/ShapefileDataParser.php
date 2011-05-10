<?php

class ShapefilePoint implements MapGeometry
{
}

class ShapefilePolyline implements MapPolyline
{
}

class ShapefilePolygon implements MapPolygon
{
}

class ShapefileDataParser extends DataParser
{
    public static $shapeTypes = array(
        '0' => 'NullShape',
        '1' => 'Point',
        '3' => 'Polyline',
        '5' => 'Polygon',
        '8' => 'MultiPoint',
        '11' => 'PointZ',
        '13' => 'PolylineZ',
        '15' => 'PolygonZ',
        '18' => 'MultiPointZ',
        '21' => 'PointM',
        '23' => 'PolylineM',
        '25' => 'PolygonM',
        '28' => 'MultiPointM',
        '31' => 'MultiPatch',
        );

    // binary parser helper functions

    public function parseData($data) {

    }

    public function parseFile($filename) {
        $fh = fopen($filename, 'rb');


    }




}


class DBase3FileParser
{








}



