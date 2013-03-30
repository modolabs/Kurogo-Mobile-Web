<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

// the unit returned by MapDataParser.
class MapCategory implements MapFolder, MapListElement
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

    public function getIdStack() {
        $categoryIds = array($this->id);
        $currentFolder = $this;
        while ($currentFolder instanceof MapCategory) {
            $currentFolder = $currentFolder->getParent();
            if ($currentFolder) {
                array_unshift($categoryIds, $currentFolder->getId());
            }
        }
        return $categoryIds;
    }

    public function addPlacemark(Placemark $placemark) {
        $categoryIds = array($this->id);
        $currentFolder = $this;
        while (($parent = $currentFolder->getParent())) {
            array_unshift($categoryIds, $parent->getId());
            $currentFolder = $parent;
        }
        foreach ($categoryIds as $id) {
            $placemark->addCategoryId($id);
        }
        $this->placemarks[] = $placemark;
    }

    public function addFolder(MapFolder $folder)
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

    public function selectPlacemark($id)
    {
        $this->setPlacemarkId($id);
        return $this->placemarks();
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

    public function categories() {
        return $this->folders;
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

    public function filterPlacemarks($filters)
    {
        $results = array();
        foreach ($this->folders as $folder) {
            $results = array_merge($results, $folder->filterPlacemarks($filters));
        }
        foreach ($this->placemarks as $placemark) {
            if ($placemark->filterItem($filters)) {
                $results[] = $placemark;
            }
        }
        return $results;
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

    // backward compatibility

    public function getListItems() {
        return array_merge($this->placemarks(), $this->categories());
    }
}

