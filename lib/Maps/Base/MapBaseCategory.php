<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// this class is only used by MapDataController which is deprecated.
// MapCategory is no longer abstract.

class MapBaseCategory extends MapCategory
{
    protected $folders = array();
    protected $features = array();

    public function __construct($id, $title) 
    {
        $this->id = $id;
        $this->title = $title;
    }

    public function getChildCategories()
    {
        return $this->folders;
    }

    public function getAllPlacemarks()
    {
        return $this->features;
    }

    public function setPlacemarks($features)
    {
        $this->features = $features;
    }

    public function getListItems()
    {
        return array_merge($this->folders, $this->features);
    }

    public function getProjection()
    {
        return null;
    }
}
