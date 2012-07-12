<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Maps', 'ArcGIS');

class ArcGISDataModel extends MapDataModel
{
    protected $DEFAULT_RETRIEVER_CLASS = 'ArcGISDataRetriever';
    protected $DEFAULT_PARSER_CLASS = 'ArcGISDataParser';

    protected function setupRetrieverForCategories() {
        $this->retriever->setAction(ArcGISDataRetriever::ACTION_CATEGORIES);
    }

    protected function setupRetrieverForPlacemarks() {
        $this->retriever->setAction(ArcGISDataRetriever::ACTION_PLACEMARKS);
    }

    protected function setCategoryId($categoryId) {
        $this->clearCategoryId();
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
    
    protected function doSearch($categories, $action) {
        $results = array();
        $this->retriever->setAction($action);

        if (count($categories) > 1) {
            foreach ($categories as $category) {
                $this->retriever->setSelectedLayer($category->getId());
                if ($action == ArcGISDataRetriever::ACTION_SEARCH) {
                    $this->retriever->setAction(ArcGISDataRetriever::ACTION_CATEGORIES);
                    $this->retriever->getData();
                    $this->retriever->setAction($action);
                }
                $results = array_merge($results, $this->returnPlacemarks($this->retriever->getData()));
            }
        } else {
            $results = $this->returnPlacemarks($this->retriever->getData());
        }
        return $results;
    }

    protected function leafCategories($categories=array()) {
        $result = array();
        if (!$categories) {
            $categories = $this->categories();
        }
        foreach ($categories as $category) {
            $children = $category->categories();
            if (!$children) {
                $result[] = $category;
            } else {
                $result = array_merge($result, $this->leafCategories($children));
            }
        }
        return $result;
    }

    // for the following search functions, the call to categories()
    // causes us to initialize internal variables like projection
    // it also causes the retriever's action to be set to "categories"
    public function search($searchTerms) {
        $categories = $this->leafCategories(); 
        $this->retriever->setSearchFilters(array('text' => $searchTerms));
        return $this->doSearch($categories, ArcGISDataRetriever::ACTION_SEARCH);
    }

    public function searchByProximity($center, $tolerance, $maxItems=0) {
        $categories = $this->leafCategories(); 
        $this->retriever->setSearchFilters(array('center' => $center, 'tolerance' => $tolerance));
        return $this->doSearch($categories, ArcGISDataRetriever::ACTION_SEARCH_NEARBY);
    }
}
