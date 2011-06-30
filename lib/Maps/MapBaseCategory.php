<?php

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

    public function getAllFeatures()
    {
        return $this->features;
    }

    public function setFeatures($features)
    {
        $this->features = $features;
    }

    public function getListItems()
    {
        return array_merge($this->folders, $this->features);
    }

    public function getListItem($path)
    {
    }
}
