<?php

interface MapFolder
{
    public function getListItems();
    public function getAllFeatures();
    public function getChildCategories();
    public function getProjection();
}

interface MapDataParser extends MapFolder
{
}
