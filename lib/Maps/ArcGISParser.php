<?php

includePackage('Maps', 'ArcGIS');

class ArcGISParser extends DataParser implements MapDataParser
{
    //private $singleFusedMapCache; // indicates whether we have map tiles
    private $initialExtent;
    private $fullExtent;
    private $serviceDescription;
    private $spatialRef;
    //private $supportedImageFormats;
    private $units;
    private $baseURL; // keep track of data controller's initial state
    private $idField;
    
    private $mapName;
    private $category;
    private $defaultLayerId;

    private $searchFilters = array();
    
    // sublayers are known to arcgis as layers
    // but we call them sublayers since we are known to our datacontroller as a layer
    private $subLayers = array();
    private $selectedLayer = null;
    private $isPopulated = false;

    public function init($args)
    {
        parent::init($args);

        $this->baseURL = $args['BASE_URL'];

        if (isset($args['ARCGIS_LAYER_ID'])) {
            $this->defaultLayerId = $args['ARCGIS_LAYER_ID'];
        }

        if (isset($args['ID_FIELD'])) {
            $this->idField = $args['ID_FIELD'];
        }
    }

    public function addSearchFilter($key, $value)
    {
        if ($this->selectedLayer) {
            $this->selectedLayer->clearCache();
        }
        $this->searchFilters[$key] = $value;
    }

    public function clearSearchFilters()
    {
        if ($this->selectedLayer) {
            $this->selectedLayer->clearCache();
        }
        $this->searchFilters = array();
    }

    public function parseData($contents)
    {
        $data = json_decode($contents, true);
        if (!$data) {
            Kurogo::log(LOG_WARNING, "Failed to get JSON response from ArcGIS server at {$this->baseURL}", 'maps');
            throw new KurogoDataServerException("The map server for this category is temporarily down.  Please try again later.");
        }
        if (isset($data['error'])) {
            $error = $data['error'];
            $code = $error['code'];
            $message = $error['message'];
            $details = isset($error['details']) ? json_encode($error['details']) : '';
            Kurogo::log(LOG_WARNING, "Error response from ArcGIS server at {$this->baseURL}:\n"
                      ."Code: $code\n"
                      ."Message: $message\n"
                      ."Details: $details\n", 'maps');
            throw new KurogoDataServerException("The map server for this category is temporarily down.  Please try again later.");
        }

        $this->serviceDescription = $data['serviceDescription'];
        //if (isset($data['supportedImageFormatTypes'])) {
        //    $this->supportedImageFormats = explode(',', $data['supportedImageFormatTypes']);
        //}
        $this->units = $data['units'];
        $this->mapName = $data['mapName'];

        $this->spatialRef = $data['spatialReference']['wkid'];
        $this->initialExtent = $data['initialExtent'];

        $this->fullExtent = $data['fullExtent'];

        // assume these are always the same as the overall spatial ref
        unset($this->initialExtent['spatialReference']);
        unset($this->fullExtent['spatialReference']);

        //$this->singleFusedMapCache = $data['singleFusedMapCache'];

        foreach ($data['layers'] as $layerData) {
            $id = $layerData['id'];
            $name = $layerData['name'];
            $this->subLayers[$id] = new ArcGISLayer($id, $name, $this);
        }
        
        if (count($this->subLayers) == 1) {
            $this->defaultLayerId = current(array_keys($this->subLayers));
        }

        if (isset($this->defaultLayerId)) {
            $this->selectDefaultLayer();
        }

        $this->isPopulated = true;

        return $this->getListItems();
    }
    
    public function getProjection() {
        return $this->spatialRef;
    }

    public function getSupportedImageFormats() {
        return $this->supportedImageFormats;
    }
    
    public function getUnits() {
        return $this->units;
    }
    
    public function getInitialExtent() {
        return $this->initialExtent;
    }
    
    public function isPopulated() {
        return $this->isPopulated;
    }
    
    public function getTitle() {
        return $this->mapName;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }
    
    //// MapFolder interface

    public function getAllPlacemarks() {
        return $this->selectedLayer->getAllPlacemarks();
    }

    public function getChildCategories() {
        return array_values($this->subLayers);
    }
    
    public function getListItems() {
        if (isset($this->defaultLayerId) || count($this->subLayers) == 1) {
            return $this->getAllPlacemarks();

        } else if ($this->searchFilters) {
            $results = array();
            foreach ($this->subLayers as $id => $layer) {
                $results = array_merge($results, $layer->getAllPlacemarks());
            }
            return $results;
        }
        return $this->getChildCategories();
    }

    ////// functions dispatched to selected layer

    public function clearCache() {
        $this->selectedLayer->clearCache();
    }
    
    public function featureFromJSON($json) {
        return $this->selectedLayer->featureFromJSON($json);
    }

    public function setIdField($field) {
        $this->idField = $field;
        if ($this->selectedLayer) {
            $this->selectedLayer->setIdField($field);
        }
    }

    public function getDefaultSearchField() {
        return $this->selectedLayer->getDisplayField();
    }

    public function getURLForSelectedLayer() {
        return $this->baseURL.'/'.$this->selectedLayer->getId();
    }

    public function getURLForLayerFeatures() {
        return $this->baseURL.'/'.$this->selectedLayer->getId().'/query';
    }
    
    public function getFiltersForLayer() {
        $filters = $this->selectedLayer->getFilters();
        foreach ($this->searchFilters as $key => $value) {
            $filters[$key] = $value;
        }
        return $filters;
    }
    
    /////// sublayer functions
    
    public function setDefaultLayer($layerId) {
        $this->defaultLayerId = $layerId;
    }
    
    public function selectDefaultLayer() {
        $this->selectSubLayer($this->defaultLayerId);
    }

    public function selectSubLayer($layerId) {
        if (isset($this->subLayers[$layerId])) {
            $this->selectedLayer = $this->getSubLayer($layerId);
            if (isset($this->idField)) {
                $this->selectedLayer->setIdField($this->idField);
            }
        }

        // parse layer contents
        if (!$this->selectedLayer->isInitialized()) {
            $this->dataController->setBaseURL($this->getURLForSelectedLayer());
            $contents = $this->dataController->getData();
            $this->selectedLayer->parseLayer($contents);
            // reset to initial state
            $this->dataController->setBaseURL($this->baseURL);
        }
    }

    public function populateFeatures() {
        if (!$this->selectedLayer->isPopulated()) {
            $this->dataController->setBaseURL($this->getURLForLayerFeatures());
            foreach ($this->getFiltersForLayer() as $key => $value) {
                $this->dataController->addFilter($key, $value);
            }
            $content = $this->dataController->getData();
            $this->selectedLayer->parseFeatures($content);

            // reset to initial state
            $this->dataController->setBaseURL($this->baseURL);
            $this->dataController->removeAllFilters();
            $this->dataController->addFilter('f', 'json');
        }
    }
    
    public function getSelectedLayerId() {
        return $this->selectedLayer->getId();
    }
    
    public function getSubLayerNames() {
        $result = array();
        foreach ($this->subLayers as $id => $sublayer) {
            $result[$id] = $sublayer->getTitle();
        }
        return $result;
    }
    
    public function getSubLayerIds() {
        return array_keys($this->subLayers);
    }

    private function getSubLayer($layerId) {
        if (isset($this->subLayers[$layerId])) {
            return $this->subLayers[$layerId];
        }
        return null;
    }

}



