<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

interface MapGeometry extends Serializable
{
    // must return an array of the form {'lat' => 2.7182, 'lon' => -3.1415}
    public function getCenterCoordinate();
}

