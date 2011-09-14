<?php

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
