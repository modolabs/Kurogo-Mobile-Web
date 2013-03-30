<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KMLFolder extends KMLDocument implements MapFolder, MapListElement
{
    protected $parent = null;
    protected $categoryId = null;
    protected $selectedPlacemarkId = null;

    protected $folders = array();
    protected $placemarks = array();

    protected function addPlacemark(Placemark $placemark)
    {
        $categoryIds = $this->getIdStack();
        foreach ($categoryIds as $id) {
            $placemark->addCategoryId($id);
        }
        $placemark->setId(count($this->placemarks));
        $this->placemarks[] = $placemark;
    }

    public function getIdStack() {
        $categoryIds = array($this->categoryId);
        $currentFolder = $this;
        while ($currentFolder instanceof KMLFolder) {
            $currentFolder = $currentFolder->getParent();
            array_unshift($categoryIds, $currentFolder->getId());
        }
        return $categoryIds;
    }

    protected function addFolder(MapFolder $folder)
    {
        $folder->setParent($this);
        $this->folders[] = $folder;
    }

    public function addItem(MapListElement $item)
    {
        if ($item instanceof Placemark) {
            $this->addPlacemark($item);
        } elseif ($item instanceof MapFolder) {
            $this->addFolder($item);
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

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(MapFolder $parent)
    {
        $this->parent = $parent;
    }

    public function setId($id) {
        $this->categoryId = $id;
    }
    
    // MapFolder interface

    public function placemarks()
    {
        if ($this->selectedPlacemarkId !== null) {
            foreach ($this->placemarks as $placemark) {
                if ($placemark->getId() == $this->selectedPlacemarkId) {
                    return array($placemark);
                }
            }
        }
        return $this->placemarks;
    }

    public function categories()
    {
        return $this->folders;
    }
    
    // MapListElement interface

    public function getSubtitle() {
        return $this->description;
    }

    public function getId() {
        return $this->categoryId;
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
