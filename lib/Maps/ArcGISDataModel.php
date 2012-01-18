<?php

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

    public function search($searchTerms) {
        $this->categories(); // retriever needs to do this to initialize internal variables like projection
        $this->retriever->setSearchFilters(array('text' => $searchTerms));
        $this->retriever->setAction(ArcGISDataRetriever::ACTION_SEARCH);
        return $this->returnPlacemarks($this->retriever->getData());
    }

    public function searchByProximity($center, $tolerance, $maxItems=0) {
        $this->categories(); // retriever needs to do this to initialize internal variables like projection
        $this->retriever->setSearchFilters(array('center' => $center, 'tolerance' => $tolerance));
        $this->retriever->setAction(ArcGISDataRetriever::ACTION_SEARCH_NEARBY);
        return $this->returnPlacemarks($this->retriever->getData());
    }
}
