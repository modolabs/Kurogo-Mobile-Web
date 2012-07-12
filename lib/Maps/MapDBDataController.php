<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
            $this->useCache = false;
        }
    }

    public function getData() {
        // TODO: this should be taken care of by PARSE_MODE_FILE
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

    public function getParsedData(DataParser $parser=null) {
        if (!$parser) {
            $parser = $this->parser;
        }

        switch ($parser->getParseMode()) {
            case DataParser::PARSE_MODE_FILE:
                break;
            default:
                $data = $this->getData();
                return $this->parseData($data, $parser);
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
        $feature->setURLParam('feed', $this->categoryId);
        $feature->setURLParam('group', $this->feedGroup);
        if ($feature) {
            $this->setSelectedPlacemarks(array($feature));
        }
        return $feature;
    }

    public function getAllPlacemarks()
    {
        $this->getListItems(); // make sure we're populated
        if ($this->hasDBData) {
            $placemarks = $this->dbParser->getCategory()->getAllPlacemarks();

        } else {
            $placemarks = $this->parser->getAllPlacemarks();
        }
        foreach ($placemarks as $placemark) {
            $placemark->setURLParam('feed', $this->categoryId);
            $placemark->setURLParam('group', $this->feedGroup);
        }
        return $placemarks;
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


