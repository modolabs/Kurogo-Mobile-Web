<?php

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
        
        $this->categoryId = mapIdForFeedData($args);
    }

    protected function returnPlacemarks(Array $placemarks) {
        $results = array();
        foreach ($placemarks as $placemark) {
            if ($placemark instanceof Placemark) {
                $placemark->addCategoryId($this->feedId);
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
    
    public function getDefaultZoomLevel()
    {
        return $this->defaultZoomLevel;
    }

    public function selectPlacemark($id)
    {
        $this->setPlacemarkId($id);
        if ($this->selectedCategory) {
            return $this->returnPlacemarks($this->selectedCategory->getPlacemark($id));
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
    }

    protected function setCategoryId($categoryId) {
        $this->selectedCategory = null;
        foreach ($this->categories() as $category) {
            if ($category->getId() == $categoryId) {
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

    public function getFeedId() {
        return $this->feedId;
    }

    public function canSearch() {
        return $this->searchable;
    }

    public function searchByProximity($center, $tolerance=1000, $maxItems=0) {
        return array();
    }

    public function search($searchTerms) {
        return array();
    }

}

