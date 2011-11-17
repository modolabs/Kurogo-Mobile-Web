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

    public function filterItem($filters)
    {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search': //case insensitive
                    return  (stripos($this->getTitle(), $value)!==FALSE) || (stripos($this->getSubTitle(), $value)!==FALSE);
                    break;
            }
        }   
        
        return true;     
    }
}



