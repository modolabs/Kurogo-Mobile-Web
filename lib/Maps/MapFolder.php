<?php

interface MapFolder
{
    public function getListItems();
    public function getAllFeatures();
    public function getChildCategories();
}

interface MapDataParser extends MapFolder
{
    public function getProjection();
}
