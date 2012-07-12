<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ArcGISFeature extends BasePlacemark
{
    private $titleField;
    private $geometryType;
    private $rawGeometry;

    public function __construct($fields, $geometry, $index, $categories)
    {
        $this->id = $index;
        $this->categories = $categories;
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

    public function serialize() {
        return serialize(
            array(
                'geometryType' => $this->geometryType,
                'titleField' => $this->titleField,
                'parent' => parent::serialize(),
            ));
    }

    public function unserialize($data) {
        $data = unserialize($data);
        parent::unserialize($data['parent']);
        $this->titleField = $data['titleField'];
        $this->geometryType = $data['geometryType'];
    }
}
