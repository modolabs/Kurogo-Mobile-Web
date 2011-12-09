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
        parent::setCategoryId($categoryId);
        $this->retriever->setSelectedLayer($categoryId);
    }



}
