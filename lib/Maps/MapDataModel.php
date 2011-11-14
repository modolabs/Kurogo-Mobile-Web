<?php

class MapDataModel extends DataModel implements MapFolder
{
    // must be set in feeds-xxx.ini
    protected $DEFAULT_PARSER_CLASS = 'KMLDataParser';
    protected $searchable = false;

    // may be set in feedgroups.ini
    // and feeds-xxx.ini; feeds-xxx.ini will take precedence
    protected $defaultZoomLevel = 16;

    // other stuff
    protected $items = null;
    protected $selectedPlacemarks = array();
    protected $allPlacemarks = array();
    protected $drillDownPath = array();

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

        // TODO: this may go away from the superclass
        $this->parser->setDataController($this);
    }

    /* public */
    
    public function getDefaultZoomLevel() {
        return $this->defaultZoomLevel;
    }

    public function selectPlacemark($featureId)
    {
        $result = null;
        foreach ($this->getAllPlacemarks() as $feature) {
            if ($feature->getId() == $featureId) {
                $result = $feature;
                break;
            }
        }
        if ($result) {
            $this->selectedPlacemarks[] = $result;
        }
        return $result;
    }

    public function setSelectedPlacemarks($features)
    {
        $this->selectedPlacemarks = $features;
    }

    public function getSelectedPlacemark()
    {
        return end($this->selectedPlacemarks);
    }

    public function getSelectedPlacemarks()
    {
        return $this->selectedPlacemarks;
    }

    public function addDisplayFilter($type, $value)
    {
        if ($type == 'category') {
            if ($value && !is_array($value)) {
                $value = array($value);
            }
            $this->drillDownPath = $value;
        }
    }

    public function clearDisplayFilters()
    {
        $this->drillDownPath = null;
    }

    public function getFilteredPlacemarks()
    {
        $features = array();
        foreach ($this->getAllPlacemarks() as $feature) {
            if (!$this->drillDownPath 
                || in_array($this->drillDownPath, $feature->getCategories()))
            {
                $features[] = $feature;
            }
        }
        return $features;
    }

    /* MapFolder interface */

    public function getChildCategories()
    {
        return $this->parser->getChildCategories();
    }

    public function getAllPlacemarks()
    {
        if (!$this->allPlacemarks) {
            $this->getListItems(); // make sure we're populated
            $this->allPlacemarks = $this->parser->getAllPlacemarks();
        }
        return $this->allPlacemarks;
    }

    public function getListItems()
    {
        return $this->listItemsAtPath(
            $this->getParsedData(), $this->drillDownPath, $this->categoryId);
    }

    public function getProjection()
    {
        return $this->parser->getProjection();
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function canSearch()
    {
        return $this->searchable;
    }

    /* not public */

    private function listItemsAtPath(array $items, array $path=array(), $otherCateogryId='something_unique')
    {
        if (count($path)) {
            $firstItem = array_shift($path);
        }
        $folders = array();
        $features = array();
        foreach ($items as $item) {
            if ($item instanceof MapFolder) {
                $folders[$item->getId()] = $item;
            } elseif ($item instanceof Placemark) {
                $features[] = $item;
            }
        }

        if (count($folders) && count($features)) {
            // put dangling placemarks at this level into a folder called "Other"
            // since we don't have UI to handle mixed folders and placemarks
            $someUniqueId = substr(md5($otherCateogryId.count($folders)), 0, strlen($otherCateogryId)-1);
            $otherCategory = new MapBaseCategory($someUniqueId, 'Other places');
            $folders[$otherCategory->getId()] = $otherCategory;
            $otherCategory->setPlacemarks($features);
        }

        if (count($folders) >= 1) {
            // attempt to drill down if we are given a "subdirectory"
            if (isset($firstItem) && isset($folders[$firstItem])) {
                return $this->listItemsAtPath(
                    $folders[$firstItem]->getListItems(), $path);
            }
            return $folders;
        }

        return $features;
    }
    
    protected function getAllLeafNodes() {
        $leafNodes = array();
        foreach ($this->getParsedData() as $item) {
            $this->getLeafNodesForListItem($item, $leafNodes);
        }
        return $leafNodes;
    }
    
    protected function getLeafNodesForListItem(MapListElement $listItem, Array &$results) {
        if ($listItem instanceof MapFolder) {
            foreach ($listItem->getListItems() as $innerItem) {
                $this->getLeafNodesForListItem($innerItem, $results);
            }
        } else {
            $results[] = $listItem;
        }
    }








}
