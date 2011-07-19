<?php

abstract class MapCategory implements MapFolder, MapListElement
{
    protected $id;
    protected $title;
    protected $description;

    public function getTitle()
    {
        return $this->title;
    }

    public function getSubtitle()
    {
        return $this->description;
    }

    public function getId()
    {
        return $this->id;
    }
}



