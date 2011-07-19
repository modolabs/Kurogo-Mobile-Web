<?php

includePackage('db');
includePackage('Maps/MapDB');

class MapDBDataController extends MapDataController implements MapFolder
{
    protected $DEFAULT_PARSER_CLASS = "MapDBDataParser";
    private $hasDBData = false;
    private $db;
    private $subtitle;

    public function getCategoryId()
    {
        return $this->db->getCategoryId();
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }

    //////// DataController overrides

    protected function initStreamContext($args)
    {
        // no stream is required if:
        
        // data is embedded
        if (isset($args['DATA_CONTAINED']) && $args['DATA_CONTAINED']) {
            return;
        }

        // we don't need to refresh our cache
        if ($this->cacheIsFresh()) {
            return;
        }

        parent::initStreamContext($args);
    }

    protected function init($args)
    {
        parent::init($args);
        $this->db = new MapDBDataParser();
        $this->db->init($args);
    }

    public function getData() {
        if ($this->parser instanceof ShapefileDataParser) {
            return;
        }
        return parent::getData();
    }

    protected function getCacheData() {
        if ($this->db->isStored() && $this->db->getCategory()->getListItems()) {
            // make sure this category was populated before skipping
            $this->hasDBData = true;
        } else {
            return parent::getCacheData();
        }
    }

    protected function parseData($data, DataParser $parser=null) {
        $items = null;
        if ($this->cacheIsFresh() && $this->hasDBData) {
            $items = $this->db->getCategory()->getListItems();
        }
        if (!$items) {
            $items = parent::parseData($data, $parser);
            $category = $this->db->getCategory();
            $category->setTitle($this->getTitle());
            $category->setSubtitle($this->getSubtitle());
            MapDB::updateCategory($category, $items);
        }
        return $items;
    }

    ////// MapDataController methods

    public function selectFeature($featureId)
    {
var_dump($featureId);
        $feature = $this->db->getFeatureById($featureId);
        if ($feature) {
            $this->setSelectedFeatures(array($feature));
        }
    }

    public function getAllFeatures()
    {
        $this->getListItems(); // make sure we're populated
        if ($this->hasDBData) {
            return $this->db->getCategory()->getAllFeatures();
        }
        return $this->parser->getAllFeatures();
    }

    // TODO allow config of searchable fields
    public function search($searchText)
    {
        /*
        $this->setSelectedFeatures($this->db->search($searchText));
        return $this->getAllSelectedFeatures();
        */
    }

    public function searchByProximity($center, $tolerance, $maxItems)
    {
        /*
        if (isset($projection)) {
            $projector = new MapProjector();
            $projector->setSrcProj($projection);
            $center = $projector->projectPoint($center);
        }

        $this->setSelectedFeatures(
            $this->db->searchByProximity($center, $tolerance, $maxItems));
        return $this->getAllSelectedFeatures();
        */
    }
}


