<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
        $totalLat = 0;
        $totalLon = 0;

        if (isset($geometry['paths'])) {
            // TODO: this assumes all paths are connected
            // this entire structure may need rethinking if this isn't the case
            $this->points = array();
            foreach ($geometry['paths'] as $currentPath) {
                foreach ($currentPath as $xy) {
                    $this->points[] = array('lon' => $xy[0], 'lat' => $xy[1]);
                }
            }
        }

        else {
            // this is how we expect geometry to be passed if constructed via
            // ArcGISPolygon
            $this->points = $geometry;
            foreach ($this->points as $point) {
                $totalLat += $point['lat'];
                $totalLon += $point['lon'];
            }
        }
        $n = count($this->points);
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
