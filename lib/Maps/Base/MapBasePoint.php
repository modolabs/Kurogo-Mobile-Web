<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapBasePoint implements MapGeometry {

    protected $centroid;

    public function __construct($coordinates, $centroid=null) {
        $this->coordinates = $coordinates;
        if ($centroid) {
            $this->centroid = $centroid;
        } else if (isset($this->coordinates['lat'], $this->coordinates['lon'])) {
            $this->centroid = $this->coordinates;
        }
    }
    
    public function getCenterCoordinate() {
        return $this->centroid;
    }

    public function serialize() {
        return serialize(
            array(
                'centroid' => serialize($this->centroid),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->centroid = unserialize($data['centroid']);
    }
}

