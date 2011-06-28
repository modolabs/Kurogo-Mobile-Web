<?php

abstract class MapCategory implements MapFolder, MapListElement
{
    protected $id;
    protected $name;
    protected $description;

    public function getTitle()
    {
        return $this->name;
    }

    public function getSubtitle()
    {
        return $this->description;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCategory()
    {
        return $this->id;
    }
}

