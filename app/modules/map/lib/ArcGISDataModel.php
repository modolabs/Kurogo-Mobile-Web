<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ArcGISDataModel extends MapDataModel
{
    protected $DEFAULT_RETRIEVER_CLASS = 'ArcGISDataRetriever';
    protected $DEFAULT_PARSER_CLASS = 'ArcGISDataParser';

    protected $searchLayers = null;

    public function init($args) {
        parent::init($args);
        if (isset($args['SEARCH_LAYERS'])) {
            $layers = explode(',', $args['SEARCH_LAYERS']);
            $this->searchLayers = array();
            foreach ($layers as $aLayer) {
                $this->searchLayers[] = trim($aLayer);
            }
        }
    }

    protected function setupRetrieverForCategories() {
        $this->retriever->setAction(ArcGISDataRetriever::ACTION_CATEGORIES);
    }

    protected function setupRetrieverForPlacemarks() {
        $this->retriever->setAction(ArcGISDataRetriever::ACTION_PLACEMARKS);
    }

    protected function setCategoryId($categoryId) {
        parent::setCategoryId($categoryId);
        if ($this->selectedCategory) {
            $this->retriever->setSelectedLayer($categoryId);
        }
    }

    public function placemarks() {
        if ($this->selectedPlacemarks) {
            return $this->returnPlacemarks($this->selectedPlacemarks);
        }
        $this->setupRetrieverForPlacemarks();
        return $this->returnPlacemarks($this->retriever->getData());
    }

    protected function leafCategories($categories=array()) {
        $result = array();
        if (!$categories) {
            $categories = $this->categories();
        }
        foreach ($categories as $category) {

            if ($this->searchLayers
                && !in_array($category->getTitle(), $this->searchLayers)
                && !in_array($category->getId(), $this->searchLayers)
            ) {
                continue;
            }

            $children = $category->categories();
            if (!$children) {
                $result[] = $category;
            } else {
                $result = array_merge($result, $this->leafCategories($children));
            }
        }
        return $result;
    }

    public function search($searchTerms) {
        $this->retriever->setSaveMemory(true);
        $categories = $this->leafCategories();
        if (!$categories) {
            return parent::search($searchTerms);
        } else {
            $results = array();
            foreach ($categories as $category) {
                $this->retriever->setSelectedLayer($category->getId());
                $results = array_merge($results, parent::search($searchTerms));
            }
        }
        $this->retriever->setSaveMemory(false);
        return $results;
    }

    public function searchByProximity($center, $tolerance, $maxItems=0) {
        $this->retriever->setSaveMemory(true);
        $categories = $this->leafCategories();
        if (!$categories) {
            return parent::searchByProximity($center, $tolerance, $maxItems);
        } else {
            $results = array();
            foreach ($categories as $category) {
                $this->retriever->setSelectedLayer($category->getId());
                $results = array_merge($results, parent::searchByProximity($center, $tolerance, $maxItems));
            }
        }
        $this->retriever->setSaveMemory(false);
        return $results;
    }

}
