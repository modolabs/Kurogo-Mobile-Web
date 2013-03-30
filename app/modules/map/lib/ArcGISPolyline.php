<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

define('STINGY_POLYLINE_POINT_LIMIT', 80);

class ArcGISPolyline implements MapPolyline
{
    protected $points;
    protected $centerCoordinate;

    public function getPoints()
    {
        return $this->points;
    }

    public function __construct($geometry, $stingy=false)
    {
        $totalLat = 0;
        $totalLon = 0;

        if (isset($geometry['paths'])) {
            // TODO: this assumes all paths are connected
            // this entire structure may need rethinking if this isn't the case
            $this->points = array();

            foreach ($geometry['paths'] as $currentPath) {
                $increment = 1;
                $count = count($currentPath);
                if ($stingy && $count > STINGY_POLYLINE_POINT_LIMIT) {
                    $increment = intval(round($count / STINGY_POLYLINE_POINT_LIMIT));
                }

                for ($i = 0; $i < $count; $i += $increment) {
                    $xy = $currentPath[$i];
                    $totalLat += $xy[1];
                    $totalLon += $xy[0];
                    $this->points[] = array('lon' => $xy[0], 'lat' => $xy[1]);
                }

                if ($stingy) break; // only use one segment
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
