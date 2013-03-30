<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapBasePolygon extends MapBasePoint implements MapPolygon {

    protected $outerBoundary;
    protected $innerBoundaries = array();

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

    public function serialize() {
        return serialize(
            array(
                'centroid' => serialize($this->centroid),
                'outerBoundary' => serialize($this->outerBoundary),
                'innerBoundaries' => serialize($this->innerBoundaries),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->centroid = unserialize($data['centroid']);
        $this->outerBoundary = unserialize($data['outerBoundary']);
        $this->innerBoundaries = unserialize($data['innerBoundaries']);
    }
}

