<?php

// sort addresses using natsort
// but move numbers to the end first
function addresscmp($addr1, $addr2) {
  $addr1 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr1);
  $addr2 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr2);
  return strnatcmp($addr1, $addr2);
}

class ArcGISPoint implements MapGeometry
{
    private $x;
    private $y;

    public function __construct($geometry)
    {
        $this->x = $geometry['x'];
        $this->y = $geometry['y'];
    }
    
    public function getCenterCoordinate()
    {
        return array('lat' => $this->y, 'lon' => $this->x);
    }
}

class ArcGISPolyline implements MapPolyline
{
    private $points;

    public function getPoints()
    {
        return $this->points;
    }

    public function __construct($geometry)
    {
        $this->points = $geometry;

        $totalLat = 0;
        $totalLon = 0;
        $n = count($this->points);
        foreach ($this->points as $point) {
            $totalLat += $point['lat'];
            $totalLon += $point['lon'];
        }
        $this->centerCoordinate = array('lat' => $totalLat / $n,
                                        'lon' => $totalLon / $n);
    }

    public function getCenterCoordinate()
    {
        return $this->centerCoordinate;
    }
}

class ArcGISPolygon implements MapPolygon
{
    private $rings;
    private $centerCoordinate;

    public function __construct($geometry)
    {
        foreach ($geometry['rings'] as $currentRing) {
            $currentRingInLatLon = array();
            foreach ($currentRing as $xy) {
                $currentRingInLatLon[] = array('lon' => $xy[0], 'lat' => $xy[1]);
            }
            
            $this->rings[] = new ArcGISPolyline($currentRingInLatLon);
        }
    }

    public function getCenterCoordinate()
    {
        $outerRing = current($this->rings);
        return $outerRing->getCenterCoordinate();
    }
    
    public function getRings() {
        return $this->rings;
    }
}

class ArcGISFeature extends BasePlacemark
{
    private $titleField;
    private $geometryType;
    private $category;
    private $rawGeometry;

    public function __construct($fields, $geometry, $index, $category)
    {
        $this->index = $index;
        $this->category = $category;
        $this->fields = $fields;
        $this->rawGeometry = $geometry;
    }
    
    public function setGeometryType($geomType)
    {
        $this->geometryType = $geomType;
        if (isset($this->rawGeometry) && !isset($this->geometry)) {
            $this->readGeometry($this->rawGeometry);
            unset($this->rawGeometry);
        }
    }
    
    public function setTitleField($field)
    {
        $this->titleField = $field;
    }
    
    public function readGeometry($json)
    {
        if (isset($this->geometryType)) {
            switch ($this->geometryType) {
                case 'esriGeometryPoint':
                    $this->geometry = new ArcGISPoint($json);
                    break;
                case 'esriGeometryPolygon':
                    $this->geometry = new ArcGISPolygon($json);
                    break;
            }
        }
    }
    
    //////// BasePlacemark overrides
    
    public function getAddress()
    {
        return $this->getField('Address');
    }

    public function getTitle()
    {
        if (isset($this->fields[$this->titleField])) {
            return $this->fields[$this->titleField];
        }
    }
    
    public function getSubtitle()
    {
    	// TODO make this a config field
        return $this->getField('Address');
    }
}

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
            error_log("Failed to get JSON response from ArcGIS server at {$this->baseURL}");
            throw new DataServerException("The map server for this category is temporarily down.  Please try again later.");
        }
        if (isset($data['error'])) {
            $error = $data['error'];
            $code = $error['code'];
            $message = $error['message'];
            $details = isset($error['details']) ? json_encode($error['details']) : '';
            error_log("Error response from ArcGIS server at {$this->baseURL}:\n"
                      ."Code: $code\n"
                      ."Message: $message\n"
                      ."Details: $details\n");
            throw new DataServerException("The map server for this category is temporarily down.  Please try again later.");
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
    /*
    public function setBaseURL($baseURL) {
        $this->baseURL = $baseURL;
    }
    */
    public function setCategory($category) {
        $this->category = $category;
    }

    public function getCategory() {
        return $this->category;
    }
    
    //// MapFolder interface

    public function getAllFeatures() {
        //if (!isset($this->selectedLayer))
        //    throw new Exception("g");
        return $this->selectedLayer->getAllFeatures();
    }

    public function getChildCategories() {
        return array_values($this->subLayers);
    }
    
    public function getListItems() {
        if (isset($this->defaultLayerId) || count($this->subLayers) == 1) {
            return $this->getAllFeatures();

        } else if ($this->searchFilters) {
            $results = array();
            foreach ($this->subLayers as $id => $layer) {
                $results = array_merge($results, $layer->getAllFeatures());
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

    //public function query($text='') {
    //    return $this->selectedLayer->query($text);
    //}

    public function setIdField($field) {
        $this->idField = $field;
        if ($this->selectedLayer) {
            $this->selectedLayer->setIdField($field);
        }
    }

    //public function getFeatureList() {
    //    return $this->selectedLayer->getFeatureList();
    //}

    public function getDefaultSearchField() {
        return $this->selectedLayer->getDisplayField();
    }
    /*
    public function selectedLayerIsPopulated() {
        return $this->selectedLayer->isPopulated();
    }
    */
    public function getURLForSelectedLayer() {
        return $this->baseURL.'/'.$this->selectedLayer->getId();
    }
    /*
    public function selectedLayerIsInitialized() {
        return $this->selectedLayer && $this->selectedLayer->isInitialized();
    }
    */
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

    /*
    private function getFeaturesForLayer($layerId) {
        return $this->selectedLayer->getListItems();
    }

    private function getFeaturesForDefaultLayer() {
        $this->getFeaturesForLayer($this->defaultLayerId);
    }
    */
    
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
        return $this->selectedLayer->getIndex();
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

class ArcGISLayer implements MapFolder, MapListElement {
    private $id;
    private $name;

    // TODO start using this
    private $parent; // ArcGISParser that created us

    private $fieldNames;
    private $extent;
    private $minScale;
    private $maxScale;

    private $idField;
    private $geometryField;
    private $displayField;

    private $spatialRef;
    private $geometryType;
    private $isInitialized = false;
    
    private $features = array();
    private $isPopulated = false;
    
    public function __construct($id, $name, ArcGISParser $parent) {
        $this->id = $id;
        $this->name = $name;
        $this->parent = $parent;
    }

    public function setIdField($field) {
        $this->idField = $field;
    }
    
    // MapListElement interface
    
    public function getId() {
        return $this->id;
    }
    
    public function getTitle() {
        return $this->name;
    }
    
    public function getSubtitle() {
        return null;
    }
    
    public function getCategory() {
        $categoryPath = $this->parent->getCategory();
        $categoryPath[] = $this->id;
        return $categoryPath;
    }
    
    //// MapFolder interface

    public function getProjection() {
        return $this->spatialRef;
    }

    public function getAllFeatures()
    {
        if (!$this->isPopulated) {
            $this->parent->selectSubLayer($this->id);
            $this->parent->populateFeatures();
        }
        return $this->features;
    }

    public function getChildCategories()
    {
        // TODO support arcgis sublayers
        return array();
    }
    
    public function getListItems() {
        // TODO support arcgis sublayers
        return $this->getAllFeatures();
    }
    
    // end MapFolder interface
    
    public function isPopulated() {
        return $this->isPopulated;
    }
    
    public function isInitialized() {
        return $this->isInitialized;
    }

    public function clearCache() {
        $this->features = array();
        $this->isPopulated = false;
    }

    public function parseLayer($contents) {
        $data = json_decode($contents, true);
        
        $this->name = $data['name'];
        $this->minScale = $data['minScale'];
        $this->maxScale = $data['maxScale'];
        $this->displayField = $data['displayField'];
        $this->geometryType = $data['geometryType'];
        $this->extent = array(
            'xmin' => $data['extent']['xmin'],
            'xmax' => $data['extent']['xmax'],
            'ymin' => $data['extent']['ymin'],
            'ymax' => $data['extent']['ymax'],
        );
        $this->spatialRef = $data['extent']['spatialReference']['wkid'];
        foreach ($data['fields'] as $fieldInfo) {
            if ($fieldInfo['type'] == 'esriFieldTypeOID') {
                if (!isset($this->idField)) {
                    $this->idField = $fieldInfo['name'];
                }
                continue;
            } else if ($fieldInfo['type'] == 'esriFieldTypeGeometry') {
                $this->geometryField = $fieldInfo['name'];
                continue;
            } else if (!isset($possibleDisplayField)
                && $fieldInfo['type'] == 'esriFieldTypeString'
            ) {
                $possibleDisplayField = $fieldInfo['name'];
            }

            $name = $fieldInfo['name'];
            if (strtoupper($name) == strtoupper($this->displayField)) {
                // handle case where display field is returned in
                // a different capitalization from return fields
                $name = $this->displayField;
            }
            $this->fieldNames[$name] = $fieldInfo['alias'];
        }

        if (!isset($this->fieldNames[$this->displayField])
            && isset($possibleDisplayField)
        ) {
            // if the display field is still problematic (e.g. the
            // OID field was returned as the display field), just
            // choose the first string field that shows up.
            // obviously if there are no other string fields then
            // this will also fail.
            $this->displayField = $possibleDisplayField;
        }

        $this->isInitialized = true;
    }
    
    public function parseFeatures($contents) {
        $data = json_decode($contents, true);

        if (isset($data['fieldAliases'])) {
            foreach ($data['fieldAliases'] as $field => $alias) {
                $this->fieldNames[$field] = $alias;
            }
        }

        $result = array();
        foreach ($data['features'] as $featureInfo) {
            $feature = $this->featureFromJSON($featureInfo);
            if ($feature) {
                $result[] = $feature;
            }
        }
        usort($result, array($this, 'compareFeatures'));
        foreach ($result as $feature) {
            $this->features[$feature->getId()] = $feature;
        }

        $this->isPopulated = true;
    }
    
    public function featureFromJSON($featureInfo) {
        if (isset($featureInfo['foundFieldName'])) { // will be set if we got here from a search
            $displayField = $featureInfo['foundFieldName'];
        } else {
            $displayField = $this->displayField;
        }

        $attribs = $featureInfo['attributes'];
        $displayAttribs = array();

        // use human-readable field alias to construct feature details
        foreach ($attribs as $name => $value) {
            if (strtoupper($name) == strtoupper($displayField)) {
                $index = $value;
            }
            if ($value !== null && trim($value) !== '') {
                if (isset($this->fieldNames[$name]))
                    $name = $this->fieldNames[$name];
                $displayAttribs[$name] = $value;
            }
        }
        if ($this->geometryType && isset($featureInfo['geometry'])) {
            $geometry = $featureInfo['geometry'];
        } else {
            $geometry = NULL;
        }

        if (!isset($displayAttribs[$this->idField])
            && !isset($displayAttribs[$this->fieldNames[$this->displayField]]))
        {
            // no usable data was included with this result
                return NULL;
        }
        
        $feature = new ArcGISFeature($displayAttribs, $geometry, $index, $this->getCategory());
        if ($this->geometryType) {
            $feature->setGeometryType($this->geometryType);
        }
        $feature->setTitleField($this->fieldNames[$this->displayField]);
        if (isset($this->idField) && isset($attribs[$this->idField])) {
            $feature->setId($attribs[$this->idField]);
        } else {
            $feature->setId($feature->getTitle());
        }
        return $feature;
    }

    public function getGeometryType() {
        return $this->geometryType;
    }

    public function getDisplayField() {
        return $this->displayField;
    }
    
    public function getFilters() {
        $bbox = $this->extent['xmin'].','.$this->extent['ymin'].','
               .$this->extent['xmax'].','.$this->extent['ymax'];
        
        $filters = array(
            'text'           => '',
            'geometry'       => $bbox,
            'geometryType'   => 'esriGeometryEnvelope',
            'inSR'           => $this->spatialRef,
            'spatialRel'     => 'esriSpatialRelIntersects',
            'where'          => '',
            'returnGeometry' => 'true',
            'outSR'          => '',
            'outFields'      => implode(',', array_keys($this->fieldNames)),
            'f'              => 'json',
        );
        
        return $filters;
    }
    
    private function compareFeatures($feature1, $feature2) {
        return addresscmp($feature1->getTitle(), $feature2->getTitle());
    }
}



