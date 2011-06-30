<?php

interface MapFolder
{
    public function getListItem($name);
    public function getListItems();
    public function getAllFeatures();
    public function getChildCategories();
}

interface MapDataParser extends MapFolder
{
}
