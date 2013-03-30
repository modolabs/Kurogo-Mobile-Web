<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
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

    // all layers that define drawingInfo have a default style.
    private $defaultStyle = null;
    // only layers that use discrete values and class breaks have a list of
    // syles in addition to the default.
    private $styles = array();

    private $styleField; // which placemark attribute is used to select style
    private $styleCriteria = array();

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
        $fieldKeys = array_keys($this->fieldAliases);
        if (isset($this->idField)) {
            $fieldKeys[] = $this->idField;
        }
        return $fieldKeys;
    }

    public function getExtent() {
        return $this->extent;
    }

    public function removePlacemark($placemark) {
        // this will typically be called as an undo, i.e. to remove the last
        // thing we added
        if ($placemark == end($this->placemarks)) {
            array_pop($this->placemarks);
        } else {
            $index = array_search($placemark, $this->placemarks);
            if ($index !== false) {
                array_splice($this->placemarks, $index, 1);
            }
        }
    }

    public function addPlacemark(Placemark $placemark) {
        parent::addPlacemark($placemark);

        // yes, defaultStyle can be null while others are defined
        if (isset($this->styleField)) {
            $value = $placemark->getField($this->styleField);
            if ($value) {
                foreach ($this->styleCriteria as $label => $matchingValue) {
                    if ((is_array($matchingValue) && $matchingValue[0] < $value && $matchingValue[1] >= $value)
                        || $value == $matchingValue)
                    {
                        $style = $this->styles[$label];
                        break;
                    }
                }
            }
        }

        if ($this->defaultStyle && !isset($style)) {
            $style = $this->defaultStyle;
        }

        if (isset($style)) {
            $placemark->setStyle($style);
        }
    }

    public function setStyleField($field) {
        $this->styleField = $field;
    }

    public function addStyleCriteria($label, $values) {
        $this->styleCriteria[$label] = $values;
    }

    // it is possible to use the same label for multiple styles in ArcMap.
    // however since legends with the same label are totally unhelpful,
    // we don't lose much information by ignoring duplicate labels
    public function addStyle($label, MapStyle $style) {
        $this->styles[$label] = $style;
    }

    public function setDefaultStyle(MapStyle $style) {
        $this->defaultStyle = $style;
    }

    public function getDefaultStyle() {
        return $this->defaultStyle;
    }
}
