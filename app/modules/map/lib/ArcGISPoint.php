<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ArcGISPoint implements MapGeometry
{
    protected $x;
    protected $y;

    public function __construct($geometry)
    {
        $this->x = $geometry['x'];
        $this->y = $geometry['y'];
    }
    
    public function getCenterCoordinate()
    {
        return array('lat' => $this->y, 'lon' => $this->x);
    }

    public function serialize() {
        return serialize(
            array(
                'x' => $this->x,
                'y' => $this->y,
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->x = $data['x'];
        $this->y = $data['y'];
    }
}
