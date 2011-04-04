<?php

class ArcGISDataController extends MapDataController
{
    protected $DEFAULT_PARSER_CLASS = 'ArcGISParser';
    protected $filters = array('f' => 'json');

    protected function cacheFileSuffix()
    {
        return '.js'; // json
    }
    
    protected function cacheFolder() 
    {
        return Kurogo::getSiteVar('ARCGIS_CACHE');
    }
    
    public function getProjection() {
        $this->initializeParser();
        return $this->parser->getProjection();
    }

    // this is mostly the same as parent class
    // but we want to eliminate categories with zero results
    public function getListItems($categoryPath=array()) {
        $container = $this;
        while (count($categoryPath) > 0) {
            $category = array_shift($categoryPath);
            $container = $container->getListItem($category);
        }
        if ($container === $this) {
            $items = $this->items();
        } else {
            $items = $container->getListItems();
        }
        
        $results = array();
        // eliminate empty categories
        foreach ($items as $item) {
            if (!($item instanceof MapFolder) || count($item->getListItems())) {
                $results[] = $item;
            }
        }
        
        // fast forward for categories that only have one item
        while (count($results) == 1) {
            $container = $results[0];
            if (!$container instanceof MapFolder) {
                break;
            }
            $results = $container->getListItems();
        }
        return $results;
    }

    public function getTitle() {
        $this->initializeParser();
        return $this->parser->getTitle();
    }
    
    public function items() {
        $this->initializeParser();
        $this->initializeLayers();
        $this->initializeFeatures();
        return $this->parser->getListItems();
    }
    
    protected function initializeParser() {
        if (!$this->parser->isPopulated()) {
            $data = $this->getData();
            $this->parseData($data);
        }
    }
    
    protected function initializeFeatures() {
        if (!$this->parser->selectedLayerIsPopulated()) {
            $oldBaseURL = $this->baseURL;
            $this->parser->setBaseURL($oldBaseURL);
            $this->baseURL = $this->parser->getURLForLayerFeatures();
            $oldFilters = $this->filters;
            $this->filters = $this->parser->getFiltersForLayer();
            $data = $this->getData();
            $this->parseData($data);
            $this->filters = $oldFilters;
            $this->baseURL = $oldBaseURL;
        }
    }
    
    protected function initializeLayers() {
        if (!$this->parser->selectedLayerIsInitialized()) {
            // set this directly so we don't interfere with cache
            $oldBaseURL = $this->baseURL;
            $this->parser->setBaseURL($oldBaseURL);
            $this->baseURL = $this->parser->getURLForSelectedLayer();
            $data = $this->getData();
            $this->parseData($data);
            $this->baseURL = $oldBaseURL;
        }
    }
    
    protected function init($args) {
        parent::init($args);
        $this->addFilter('f', 'json');
    }
    
    public function search($searchText) {
        $this->initializeParser();
        $this->initializeLayers();

        $oldBaseURL = $this->baseURL;
        $this->parser->setBaseURL($oldBaseURL);
        $this->baseURL = $this->parser->getURLForLayerFeatures();
        
        $data = $this->getData();
        $this->parseData($data);
        
        // restore previous state
        $this->baseURL = $oldBaseURL;
        
        return $this->items();
    }
    
    public function searchByProximity($center, $tolerance, $maxItems) {
        
        // TODO: these units are completely wrong (but work for harvard b/c
        // their units are in feet); we should use MapProjector to get
        // a decent range
        $dLatDegrees = $tolerance;
        $dLonDegrees = $tolerance;

        $maxLat = $center['lat'] + $dLatDegrees;
        $minLat = $center['lat'] - $dLatDegrees;
        $maxLon = $center['lon'] + $dLonDegrees;
        $minLon = $center['lon'] - $dLonDegrees;
        
        $this->initializeParser();
        $this->initializeLayers();

        $oldBaseURL = $this->baseURL;
        $this->parser->setBaseURL($oldBaseURL);
        $this->baseURL = $this->parser->getURLForLayerFeatures();
        $this->addFilter('geometry', "$minLon,$minLat,$maxLon,$maxLat");
        $this->addFilter('geometryType', 'esriGeometryEnvelope');
        $this->addFilter('spatialRel', 'esriSpatialRelIntersects');
        $this->addFilter('returnGeometry', 'false');
        $data = $this->getData();
        $this->parseData($data);
        
        // restore previous state
        $this->baseURL = $oldBaseURL;
        $this->removeAllFilters();
        $this->addFilter('f', 'json');
        
        return $this->getAllLeafNodes();
    }
    
    // TODO make a standalone method in ArcGISParser that
    // that doesn't require us to create a throwaway controller
    public static function parserFactory($baseURL) {
        $throwawayController = new ArcGISDataController();
        $throwawayController->init(array('BASE_URL' => $baseURL));
        $data = $throwawayController->getData();
        $throwawayController->parseData($data);
        return $throwawayController->parser;
    }
    
}

