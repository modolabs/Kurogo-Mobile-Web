<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class SimpleXMLDataParser extends DataParser {
    protected $multiValueElements = array();
    protected $simpleMode = false;
    
    public function init($args) {
        parent::init($args);
    
        if (isset($args['SIMPLE_MODE'])) {
            $this->simpleMode = $args['SIMPLE_MODE'];
        }
    }

    public function parseData($data) {
        try {
            $xml = new SimpleXMLElement($data, LIBXML_NOCDATA);
        } catch (Exception $e) {
            return null;
        }

        if ($this->simpleMode) {
            $data = json_decode(json_encode($xml), TRUE);
        } else {
            $data = $this->xmlObjToArr($xml); 
        }
        return $data;
    }

    protected function isNumericArray($arr) {
        return is_array($arr) && (count($arr) > 0) && (array_keys($arr) === range(0, count($arr)-1));
    }

    protected function xmlObjToArr($obj) {
        // get all namespaces
        $namespace = $obj->getDocNamespaces(true);
        // entrance for none namespace node
        $namespace[null] = null;

        $children = array();
        $name = strtolower((string)$obj->getName());

        $text = trim((string)$obj);
        if( strlen($text) <= 0 ) {
            $text = null;
        }else {
            // current element is a text element just return the value
            return $text;
        }

        // get info for all namespaces
        if(is_object($obj)) {
            foreach( $namespace as $ns => $nsUrl ) {
                // children
                $objChildren = $obj->children($ns, true);

                foreach ($obj->attributes($nsUrl) as $attrib=>$value) {
                    $children['@attributes'][$attrib] = strval($value);
                }

                foreach( $objChildren as $childName=>$child ) {
                    $childName = strtolower((string)$childName);
                    if( !empty($ns) ) {
                        $childName = $ns . ':' . $childName;
                    }
                    $childArray = $this->xmlObjToArr($child);
                    
                    if (is_string($childArray)) {
                        $children[$childName] = $childArray;
                    } elseif (
                        count($childArray)==0 || 
                       (count($childArray)==1 && isset($childArray[0]) && !is_array($childArray[0]))) {
                        //if the array has a single element and it is not an array then it's just a value
                        $children[$childName] = $childArray;
                    } elseif (in_array($childName, $this->multiValueElements)) { 
                        //specifically called out as a multi value element
                        $children[$childName][] = $childArray;
                    } elseif (isset($children[$childName])) {
                        if (!is_array($children[$childName])) {
                            $children[$childName] = array($children[$childName]);
                        }
                    
                        //if the value already exists then make an array of values
                        if (array_keys($children[$childName]) !== range(0, count($children[$childName])-1)) {
                            //it's the first one (it's not an array of numeric indicies)
                            $childNameFirstChild = $children[$childName];
                            $children[$childName] = array($childNameFirstChild);
                        }
                        $children[$childName][] = $childArray;
                    } else {
                        //it's an array where the child doesn't exist
                        $children[$childName] = $childArray;
                    }
                }
            }
        }

        // make sure empty array is set to null
        if(empty($children)) {
            $children = null;
        }

        return $children;
    }
}
