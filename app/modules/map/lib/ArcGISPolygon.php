<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

define('STINGY_POLYGON_POINT_LIMIT', 80);

class ArcGISPolygon implements MapPolygon
{
    protected $rings;
    protected $centerCoordinate;

    public function __construct($geometry, $stingy=false)
    {
        foreach ($geometry['rings'] as $currentRing) {
            $count = count($currentRing);
            $increment = 1;
            if ($stingy && $count > STINGY_POLYGON_POINT_LIMIT) {
                $increment = intval(round($count / STINGY_POLYGON_POINT_LIMIT));
            }

            $currentRingInLatLon = array();
            for ($i = 0; $i < $count; $i += $increment) {
                $xy = $currentRing[$i];
                $currentRingInLatLon[] = array('lon' => $xy[0], 'lat' => $xy[1]);
            }
            
            $this->rings[] = new ArcGISPolyline($currentRingInLatLon);

            if ($stingy) break; // use outer ring only
        }
    }

    public function getCenterCoordinate()
    {
        reset($this->rings);
        $outerRing = current($this->rings);
        return $outerRing->getCenterCoordinate();
    }
    
    public function getRings() {
        return $this->rings;
    }

    public function serialize() {
        return serialize(
            array(
                'rings' => serialize($this->rings),
                'centerCoordinate' => serialize($this->centerCoordinate),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->rings = unserialize($data['rings']);
        $this->centerCoordinate = unserialize($data['centerCoordinate']);
    }
}
