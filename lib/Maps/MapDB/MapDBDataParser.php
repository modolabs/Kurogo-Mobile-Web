<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class MapDBDataParser extends DataParser implements MapDataParser
{
    private $category = null;
    private $categoryId;

    public function placemarks() {
        return $this->getAllPlacemarks();
    }

    public function categories() {
        return $this->getChildCategories();
    }

    public function getId() {
        return $this->categoryId;
    }

    // MapDataParser interface

    public function getProjection()
    {
        return $this->getCategory()->getProjection();
    }

    public function getListItems()
    {
        return array_merge($this->getChildCategories(), $this->getAllPlacemarks());
    }

    public function getAllPlacemarks()
    {
        return MapDB::featuresForCategory($this->categoryId);
    }

    public function getChildCategories()
    {
        return MapDB::childrenForCategory($this->categoryId);
    }

    // overrides

    public function init($args) {
        parent::init($args);
        $this->categoryId = mapIdForFeedData($args);
    }

    public function parseData($data) {
        // do nothing
    }

    // everything else

    public function isStored() {
        return $this->getCategory()->isStored();
    }

    public function getCategoryId()
    {
        return $this->categoryId;
    }

    public function getChild($childId) {
        return self::getChildForCategory($childId, $this->categoryId);
    }

    public function getCategory() {
        if (!$this->category) {
            $this->category = MapDB::categoryForId($this->categoryId);
        }
        return $this->category;
    }

    public function getFeatureById($featureId, $possibleCategories=array())
    {
        $possibleCategories[] = $this->categoryId;
        return MapDB::getFeatureByIdAndCategory($featureId, $possibleCategories);
    }
}
