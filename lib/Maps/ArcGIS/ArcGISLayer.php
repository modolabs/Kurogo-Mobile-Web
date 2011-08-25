<?php

// sort addresses using natsort
// but move numbers to the end first
function addresscmp($addr1, $addr2) {
  $addr1 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr1);
  $addr2 = preg_replace('/^([\d\-\.]+)(\s*)(.+)/', '${3}${2}${1}', $addr2);
  return strnatcmp($addr1, $addr2);
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

    public function getAllPlacemarks()
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
        return $this->getAllPlacemarks();
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
