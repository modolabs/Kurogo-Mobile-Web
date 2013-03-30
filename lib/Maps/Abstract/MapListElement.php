<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// implemented by map categories, which have no geometry
interface MapListElement extends KurogoObject
{
    public function getTitle();
    public function getSubtitle();
}

