<?php

includePackage('Maps', 'MapDB');

class MapDBDataController extends MapDataController implements MapFolder
{
    protected $DEFAULT_PARSER_CLASS = "MapDBDataParser";
    private $hasDBData = false;
    private $dbParser;
    private $subtitle;

    protected $cacheLifetime = 60;

    public function getCategoryId()
    {
        return $this->dbParser->getCategoryId();
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    //////// DataController overrides

    protected function initStreamContext($args)
    {
        // no stream is required if:

        // we don't need to refresh our cache
        if ($this->cacheIsFresh()) {
            return;
        }

        parent::initStreamContext($args);
    }

    protected function init($args)
    {
        parent::init($args);
        $this->dbParser = new MapDBDataParser();
        $this->dbParser->init($args);
        if ($this->dbParser->isStored() && $this->dbParser->getCategory()->getListItems()) {
            // make sure this category was populated before skipping
            $this->hasDBData = true;
        }
    }

    public function getData() {
        if ($this->parser instanceof ShapefileDataParser) {
            return;
        }
        return parent::getData();
    }

    protected function getCacheData() {
        // if data is in db, do nothing
        if (!$this->hasDBData) {
            return parent::getCacheData();
        }
    }

    protected function parseData($data, DataParser $parser=null) {
        $items = null;
        if ($this->cacheIsFresh() && $this->hasDBData) {
            $items = $this->dbParser->getCategory()->getListItems();
        }
        if (!$items) {
            $items = parent::parseData($data, $parser);
            $projection = $this->getProjection();

            $category = $this->dbParser->getCategory();
            $category->setTitle($this->getTitle());
            $category->setSubtitle($this->getSubtitle());
            MapDB::updateCategory($category, $items, $projection);
            //$items = $category->getListItems();
            $this->hasDBData = true;
        }
        return $items;
    }

    public function getProjection()
    {
        if ($this->cacheIsFresh() && $this->hasDBData) {
            // features are converted to lat/lon when stored
            return null;
        }
        return parent::getProjection(); // returns parent's parser's projection
    }

    ////// MapDataController methods

    public function selectPlacemark($featureId)
    {
        $feature = $this->dbParser->getFeatureById($featureId, $this->drillDownPath);
        if ($feature) {
            $this->setSelectedPlacemarks(array($feature));
        }
        return $feature;
    }

    public function getAllPlacemarks()
    {
        $this->getListItems(); // make sure we're populated
        if ($this->hasDBData) {
            return $this->dbParser->getCategory()->getAllPlacemarks();
        }
        return $this->parser->getAllPlacemarks();
    }

    // TODO allow config of searchable fields
    public function search($searchText)
    {
        return array();
    }

    public function searchByProximity($center, $tolerance, $maxItems=null)
    {
        $mapSearch = new MapDBSearch(null);
        return $mapSearch->searchByProximity($center, $tolerance, $maxItems, $this);
    }
}


