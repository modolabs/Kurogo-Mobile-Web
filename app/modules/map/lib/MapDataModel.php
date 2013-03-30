<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapDataModel extends DataModel implements MapFolder
{
    // must be set in feeds-xxx.ini
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $searchable = false;

    // may be set in feedgroups.ini
    // and feeds-xxx.ini; feeds-xxx.ini will take precedence
    protected $defaultZoomLevel = 16;

    protected $feedId;
    protected $categories = array();
    protected $selectedCategory;
    protected $feedGroup;

    // other stuff
    protected $items = null;
    protected $selectedPlacemarks = array();

    protected function init($args)
    {
        parent::init($args);

        if (isset($args['SEARCHABLE'])) {
            $this->searchable = $args['SEARCHABLE'] == 1;
        }

        if (isset($args['DEFAULT_ZOOM_LEVEL'])) {
            $this->defaultZoomLevel = $args['DEFAULT_ZOOM_LEVEL'];
        }

        if (isset($args['group'])) {
            $this->feedGroup = $args['group'];
        }
        
        $this->feedId = mapIdForFeedData($args);
    }

    protected function returnPlacemarks(Array $placemarks) {
        $results = array();
        foreach ($placemarks as $placemark) {
            if ($placemark instanceof Placemark) {
                $this->addURLParams($placemark);
                $results[] = $placemark;
            }
        }
        return $results;
    }

    protected function returnCategories(Array $categories) {
        $results = array();
        foreach ($categories as $category) {
            if ($category instanceof MapFolder) {
                $results[] = $category;
            }
        }
        return $results;
    }

    /* public */

    public function setFeedGroup($group) {
        $This->feedGroup = $group;
    }
    
    public function getDefaultZoomLevel()
    {
        return $this->defaultZoomLevel;
    }

    public function selectPlacemark($id)
    {
        $this->setPlacemarkId($id);
        if ($this->selectedCategory) {
            return $this->returnPlacemarks($this->selectedCategory->selectPlacemark($id));
        }
        elseif ($this->selectedPlacemarks) {
            return $this->returnPlacemarks($this->selectedPlacemarks);
        }
    }

    public function setSelectedPlacemarks($features)
    {
        $this->selectedPlacemarks = $features;
    }

    public function getSelectedPlacemarks()
    {
        return $this->returnPlacemarks($this->selectedPlacemarks);
    }

    public function findCategory($categoryArg) {
        foreach (explode(MAP_CATEGORY_DELIMITER, $categoryArg) as $categoryId) {
            if (strlen($categoryId)) {
                $this->setCategoryId($categoryId);
            }
        }

        if ($this->selectedCategory) {
            return $this->selectedCategory;
        }
        return $this;
    }

    public function clearCategoryId() {
        $this->selectedCategory = null;
    }

    protected function setCategoryId($categoryId) {
        if (!strlen($categoryId)) {
            throw new KurogoException("Invalid category ID");
        }
        foreach ($this->categories() as $category) {
            if (strval($category->getId()) == strval($categoryId)) {
                $this->selectedCategory = $category;
                break;
            }
        }
    }

    public function setPlacemarkId($placemarkId) {
        if ($this->selectedCategory) {
            $this->selectedCategory->setPlacemarkId($placemarkId);
        }
        else {
            foreach ($this->placemarks() as $placemark) {
                if ($placemark->getId() == $placemarkId) {
                    $this->selectedPlacemarks = array($placemark);
                    break;
                }
            }
        }
    }

    protected function setupRetrieverForCategories() {}
    protected function setupRetrieverForPlacemarks() {}

    public function categories() {
        if ($this->selectedCategory) {
            return $this->selectedCategory->categories();
        }
        $this->setupRetrieverForCategories();
        return $this->returnCategories($this->retriever->getData());
    }

    public function placemarks() {
        if ($this->selectedPlacemarks) {
            return $this->returnPlacemarks($this->selectedPlacemarks);
        }
        if ($this->selectedCategory) {
            return $this->returnPlacemarks($this->selectedCategory->placemarks());
        }
        $this->setupRetrieverForPlacemarks();
        return $this->returnPlacemarks($this->retriever->getData());
    }

    public function items() {
        return array_merge($this->categories(), $this->placemarks());
    }

    public function getFeedId() {
        return $this->feedId;
    }

    public function canSearch() {
        return $this->searchable;
    }

    protected function addURLParams($placemark) {
        $placemark->setURLParam('feed', $this->getFeedId());
        if (isset($this->feedGroup)) {
            $placemark->setURLParam('group', $this->feedGroup);
        }
    }

    protected function filterPlacemarks($filters) {
        $results = array();
        foreach ($this->categories() as $category) {
            foreach ($category->filterPlacemarks($filters) as $placemark) {
                $this->addURLParams($placemark);
                $results[] = $placemark;
            }
        }
        foreach ($this->placemarks() as $placemark) {
            if ($placemark->filterItem($filters)) {
                $this->addURLParams($placemark);
                $results[] = $placemark;
            }
        }
        return $results;
    }

    // argument must be lat/lon (not projected)
    // MapSearch class will take care of sorting by distance
    public function searchByProximity($center, $tolerance, $maxItems=0) {
        $bbox = normalizedBoundingBox($center, $tolerance);
        $results = $this->filterPlacemarks($bbox);
        return $results;
    }

    public function search($searchTerms) {
        $results = $this->filterPlacemarks(array('search' => $searchTerms));
        return $results;
    }

}

