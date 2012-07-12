<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ArcGISFolder extends MapCategory
{
    private $displayField;
    private $idField;
    private $geometryField;
    private $geometryType;
    private $extent;

    private $fieldAliases = array();

    public function findCategory($folderId) {
        foreach ($this->categories() as $category) {
            if ($category->getId() == $folderId) {
                return $category;
            }
        }
        return null;
    }

    public function setSubtitle($subtitle) {
        $this->description = $subtitle;
    }

    public function setDisplayField($field) {
        $this->displayField = $field;
    }

    public function setGeometryType($type) {
        $this->geometryType = $type;
    }

    public function setExtent($extent) {
        $this->extent = $extent;
    }

    public function setIdField($field) {
        $this->idField = $field;
    }

    public function setGeometryField($field) {
        $this->geometryField = $field;
    }

    public function setFieldAlias($name, $alias) {
        $this->fieldAliases[$name] = $alias;
    }

    public function getGeometryType() {
        return $this->geometryType;
    }

    public function getDisplayField() {
        return $this->displayField;
    }

    public function getIdField() {
        return $this->idField;
    }

    public function hasField($name) {
        return isset($this->fieldAliases[$name]);
    }

    public function aliasForField($name) {
        if (isset($this->fieldAliases[$name])) {
            return $this->fieldAliases[$name];
        }
        return $name;
    }

    public function getFieldKeys() {
        return array_keys($this->fieldAliases);
    }

    public function getExtent() {
        return $this->extent;
    }

}
