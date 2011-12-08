<?php

// the unit returned by MapDataParser.
class MapCategory implements MapFolder, MapListElement
//, MapListElement
{
    protected $parent;
    protected $id;
    protected $title;
    protected $description;
    protected $selectedPlacemarkId;

    protected $folders = array();
    protected $placemarks = array();

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

    protected function addPlacemark(Placemark $placemark)
    {
        $categoryIds = array($this->id);
        $currentFolder = $this;
        while (($parent = $currentFolder->getParent())) {
            array_unshift($categoryIds, $parent->getId());
        }
        foreach ($categoryIds as $id) {
            $placemark->addCategoryId($id);
        }
        $this->placemarks[] = $placemark;
    }

    protected function addFolder(MapFolder $folder)
    {
        $folder->setParent($this);
        $this->folders[] = $folder;
    }

    public function addItem(MapListElement $item)
    {
        if ($item instanceof Placemark) {
            $this->placemarks[] = $item;
        } elseif ($item instanceof MapFolder) {
            $this->folders[] = $item;
        }
    }

    public function setPlacemarkId($placemarkId)
    {
        $this->selectedPlacemarkId = $placemarkId;
    }

    public function placemarks()
    {
        if ($this->selectedPlacemarkId) {
            $result = array();
            foreach ($this->placemarks as $placemark) {
                if ($placemark->getId() == $this->selectedPlacemarkId) {
                    return array($placemark);
                }
            }
        }
        return $this->placemarks;
    }

    public function selectPlacemark($id)
    {
        $this->setPlacemarkId($id);
        return $this->placemarks();
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(MapFolder $parent)
    {
        $this->parent = $parent;
    }

    public function __construct($id, $title, $description=null, $parent=null) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->parent = $parent;
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

