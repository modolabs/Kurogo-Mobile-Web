<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class KurogoDataObject implements KurogoObject {
  
  protected $id;
  protected $title;
  protected $description;
  protected $attributes = array();
  
  public function __toString() {
    return $this->getTitle();
  }
  
  public function setID($id) {
    $this->id = $id;
  }
  
  public function getID() {
    return $this->id;
  }
  
  public function setTitle($title) {
    $this->title = $title;
  }
  
  public function getTitle() {
    return $this->title;
  }
  
  public function setDescription($description) {
    $this->description = $description;
  }
  
  public function getDescription() {
    return $this->description;
  }

  public function hasAttribute($attributeName) {
    return array_key_exists($attributeName, $this->attributes);
  }

  /*
   * Returns an attribute from the attribute array keyed by name.
   *
   * @param attributeName
   * The name of the attribute to get
   *
   * @param default
   * The default value to return if the attribute is not set. By default it 
   * is null.
   */
  public function getAttribute($attributeName, $default = null) {
    if (array_key_exists($attributeName, $this->attributes)) {
      return $this->attributes[$attributeName];
    } else {
      return $default;
    }
  }
  
  public function setAttribute($attributeName, $attributeValue) {
    $this->attributes[$attributeName] = $attributeValue;
  }
  
  /*
   * Sets multiple attributes at once
   *
   * @param attrArray
   * An array where the keys are the attribute names and the values are the
   * associated attribute value for that attribute.
   */
  public function setAttributes($attrArray) {
    foreach ($attrArray as $attributeName => $attributeValue) {
      $this->setAttribute($attributeName, $attributeValue);
    }
  }
  
  /*
   * Removes an attribute from the attribute array. Returns true if an attribute
   * was unset, false if the attribute was never set.
   *
   * @param attributeName
   * The attribute to unset
   */
  public function removeAttribute($attributeName) {
    if (array_key_exists($attributeName, $this->attributes)) {
      unset($this->attributes[$attributeName]);
      return true;
    } else {
      return false;
    }
  }
  
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }
 
        return true;
    }
  
}