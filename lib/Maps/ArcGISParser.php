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

class ArcGISPolygon implements MapPolygon
{
    private $rings;
    private $centerCoordinate;

    public function __construct($geometry)
    {
        // for center, just use outermost ring
        $numVertices = 0;
        $totalX = 0;
        $totalY = 0;
        $currentRing = array();
        if (count($geometry['rings'])) {
            $currentRing = $geometry['rings'][0];
            $currentRingInLatLon = array();
            $numVertices = count($currentRing);
            foreach ($currentRing as $xy) {
                $totalX += $xy[0];
                $totalY += $xy[1];
                
                $currentRingInLatLon[] = array('lon' => $xy[0], 'lat' => $xy[1]);
            }
            $this->centerCoordinate = array('lat' => $totalY / $numVertices,
                                            'lon' => $totalX / $numVertices);
            $this->rings[] = $currentRingInLatLon;
        }

        for ($i = 1; $i < count($geometry['rings']); $i++) {
            $currentRing = $geometry['rings'][$i];
            $currentRingInLatLon = array();
            foreach ($currentRing as $xy) {
                $currentRingInLatLon[] = array('lon' => $xy[0], 'lat' => $xy[1]);
            }
            
            $this->rings[] = $currentRingInLatLon;
        }
    }

    public function getCenterCoordinate()
    {
        return $this->centerCoordinate;
    }
    
    public function getRings() {
        return $this->rings;
    }
}

class ArcGISFeature implements MapFeature
{
    private $index;
    private $attributes;
    private $geometry;
    private $titleField;
    private $geometryType;
    private $category;
    
    // if we want to turn off display for certain fields
    // TODO put this in a more accessible place
    private $blackList = array();
    
    public function __construct($attributes, $geometry=null, $index=null, $category=null)
    {
        $this->index = $index;
        $this->category = $category;
        $this->attributes = $attributes;
        $this->geometry = $geometry;
    }
    
    public function setId($id) {
        $this->attributes['modolabs:_id'] = $id;
        $this->setIdField('modolabs:_id');
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }
    
    public function setGeometryType($geomType)
    {
        $this->geometryType = $geomType;
    }
    
    public function setTitleField($field)
    {
        $this->titleField = $field;
    }
    
    public function getField($fieldName)
    {
        if (isset($this->attributes[$fieldName])) {
            return $this->attributes[$fieldName];
        }
        return null;
    }
    
    public function setField($fieldName, $value)
    {
        $this->attributes[$fieldName] = $value;
    }
    
    public function setBlackList($fields) {
        $this->blackList = $fields;
    }
    
    public function readGeometry($json)
    {
        $this->geometry = $json;
    }
    
    //////// MapFeature interface
    
    public function getCategory() {
        return $this->category;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getTitle()
    {
        return $this->attributes[$this->titleField];
    }
    
    public function getSubtitle()
    {
    	// TODO make this a config field
        return $this->getField('Address');
    }
    
    public function getGeometry()
    {
        $geometry = null;
        if ($this->geometry !== null) {
            switch ($this->geometryType) {
            case 'esriGeometryPoint':
                $geometry = new ArcGISPoint($this->geometry);
                break;
            case 'esriGeometryPolygon':
                $geometry = new ArcGISPolygon($this->geometry);
                break;
            }
        }
        return $geometry;
    }
    
    public function setGeometry(MapGeometry $geometry) {
        if ($geometry instanceof MapPolygon) {
            $this->geometry = $geometry->getRings();
        } else {
            $this->geometry = $geometry->getCenterCoordinate();
        }
    }
    
    public function getDescriptionType()
    {
    	return MapFeature::DESCRIPTION_LIST;
    }
    
    public function getDescription()
    {
    	$details = array();
        foreach ($this->attributes as $name => $value) {
            if (!in_array($name, $this->blackList)) {
                $aDetail = array('label' => $name, 'title' => $value);
                // There is a bug in some versions of filter_var where it can't handle hyphens in hostnames
                if (filter_var(strtr($value, '-', '.'), FILTER_VALIDATE_URL)) {
                    $aDetail['url'] = $value;
                    $aDetail['class'] = 'external';
                }
            	$details[] = $aDetail;
            }
        }
        return $details;
    }

    public function getStyle()
    {
        return null;
    }
}

class ArcGISParser extends DataParser implements MapFolder
{
    private $singleFusedMapCache; // indicates whether we have map tiles
    private $initialExtent;
    private $fullExtent;
    private $serviceDescription;
    private $spatialRef;
    private $supportedImageFormats;
    private $units;
    private $baseURL;
    
    private $mapName;
    private $categoryId;
    private $defaultLayerId = 0;
    
    // sublayers are known to arcgis as layers
    // but we call them sublayers since we are known to our datacontroller as a layer
    private $subLayers = array();
    private $selectedLayer = null;
    private $isPopulated = false;

    public function parseData($contents)
    {
        if (!$this->isPopulated) { // initial parse
            $data = json_decode($contents, true);
            if (!$data) {
                error_log("Failed to get JSON response from ArcGIS server at {$this->baseURL}";
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
            $this->supportedImageFormats = explode(',', $data['supportedImageFormatTypes']);
            $this->units = $data['units'];
            $this->mapName = $data['mapName'];

            $this->spatialRef = $data['spatialReference']['wkid'];
            $this->initialExtent = $data['initialExtent'];

            $this->fullExtent = $data['fullExtent'];

            // assume these are always the same as the overall spatial ref
            unset($this->initialExtent['spatialReference']);
            unset($this->fullExtent['spatialReference']);

            $this->singleFusedMapCache = $data['singleFusedMapCache'];

            foreach ($data['layers'] as $layerData) {
                $id = $layerData['id'];
                $name = $layerData['name'];
                $this->subLayers[$id] = new ArcGISLayer($id, $name, $this->categoryId);
            }
            
            $this->selectDefaultLayer();
            $this->isPopulated = true;

        } else {
            $this->selectedLayer->parseData($contents);
        }
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
    
    public function setBaseURL($baseURL) {
        $this->baseURL = $baseURL;
    }
    
    public function setCategoryId($categoryId) {
        $this->categoryId = $categoryId;
    }
    
    //// MapFolder interface
    
    public function getListItems() {
        return array_values($this->subLayers);
    }
    
    public function getListItem($name) {
        return $this->getSubLayer($name);
    }

    ////// functions dispatched to selected layer
    
    public function featureFromJSON($json) {
        return $this->selectedLayer->featureFromJSON($json);
    }

    public function query($text='') {
        return $this->selectedLayer->query($text);
    }

    //public function getFeatureList() {
    //    return $this->selectedLayer->getFeatureList();
    //}

    public function getDefaultSearchField() {
        return $this->selectedLayer->getDisplayField();
    }
    
    public function selectedLayerIsPopulated() {
        return $this->selectedLayer->isPopulated();
    }
    
    public function getURLForSelectedLayer() {
        return $this->baseURL.'/'.$this->selectedLayer->getIndex();
    }
    
    public function selectedLayerIsInitialized() {
        return $this->selectedLayer && $this->selectedLayer->isInitialized();
    }
    
    public function getURLForLayerFeatures() {
        return $this->baseURL.'/'.$this->selectedLayer->getIndex().'/query';
    }
    
    public function getFiltersForLayer() {
        return $this->selectedLayer->getFilters();
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
    private $parentId;
    
    private $fieldNames;
    private $extent;
    private $minScale;
    private $maxScale;
    private $displayField;
    private $spatialRef;
    private $geometryType;
    private $isInitialized = false;
    
    private $features = array();
    private $isPopulated = false;
    
    public function __construct($id, $name, $parentId) {
        $this->id = $id;
        $this->name = $name;
        $this->parentId = $parentId;
    }
    
    // MapListElement interface
    
    public function getIndex() {
        return $this->id;
    }
    
    public function getTitle() {
        return $this->name;
    }
    
    public function getSubtitle() {
        return null;
    }
    
    public function getCategory() {
        return array($this->parentId, $this->id);
    }
    
    //// MapFolder interface
    
    public function getListItems() {
        return $this->features;
    }
    
    public function getListItem($name) {
        if (isset($this->features[$name])) {
            return $this->features[$name];
        }
        return null;
    }
    
    // end MapFolder interface
    
    public function isPopulated() {
        return $this->isPopulated;
    }
    
    public function isInitialized() {
        return $this->isInitialized;
    }
    
    public function parseData($contents) {
        $data = json_decode($contents, true);

        if (!$this->isInitialized) {
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
                // often the field names will be full paths to SQL tables,
                // as in database.table or server.scheme.database.table
                //$nameRefParts = explode('.', $fieldInfo['name']);
                //var_dump($fieldInfo);
                //$name = end($nameRefParts);
                $name = $fieldInfo['name'];
                $this->fieldNames[$name] = $fieldInfo['alias'];
            }
    
            $this->isInitialized = true;
        } else if (!$this->isPopulated) {
            $result = array();
            foreach ($data['features'] as $featureInfo) {
                $feature = $this->featureFromJSON($featureInfo);
                if ($feature) {
                    $result[] = $feature;
                }
            }
            usort($result, array($this, 'compareFeatures'));
            foreach ($result as $feature) {
                $this->features[$feature->getTitle()] = $feature;
            }

            $this->isPopulated = true;
        }
    }
    
    public function featureFromJSON($featureInfo) {
        $attribs = $featureInfo['attributes'];
        $displayAttribs = array();
        // use human-readable field alias to construct feature details
        foreach ($attribs as $name => $value) {
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
        
        if (!$displayAttribs && !$geometry) { // we basically got empty JSON, so don't create anything
            return NULL;
        }
        
        // doing this assumes the display names for buildings are unique
        // this is because we have no way of figuring out the object's actual ID
        $index = $attribs[$this->displayField];
        $feature = new ArcGISFeature($displayAttribs, $geometry, $index, $this->getCategory());
        if ($this->geometryType) {
            $feature->setGeometryType($this->geometryType);
        }
        $feature->setTitleField($this->fieldNames[$this->displayField]);
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



