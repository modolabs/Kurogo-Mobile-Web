<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class XMLElement
{
    protected $attribs=array();
    protected $name;
    protected $value;
    protected $debugMode = false;
    protected $properties = array();
    protected $strip_tags = false;
    protected $html_decode = false;
    protected $encoding;
    
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }
    
    public function __construct($name, $attribs, $encoding='UTF-8')
    {
        $this->setName($name);
        $this->setAttribs($attribs);
        $this->encoding = $encoding;
    }
    
    public function shouldStripTags($strip_tags)
    {
        $this->strip_tags = $strip_tags;
    }
    
    // For buggy feeds which have elements escaped with both CDATA and html entities
    public function shouldHTMLDecodeCDATA($html_decode)
    {
        $this->html_decode = $html_decode;
    }
    
    public function setAttribs($attribs)
    {
        if (is_array($attribs)) {
            $this->attribs = $attribs;
        }
    }

    public function getAttrib($attrib)
    {
        return isset($this->attribs[strtoupper($attrib)]) ? $this->attribs[strtoupper($attrib)] : null;
    }
    
    public function getAttribs()
    {
        return $this->attribs;
    }
    
    public function setValue($value, $strip_tags=false /* compat arg */)
    {
        if ($strip_tags) {
            $this->strip_tags = true;
        }
        
        $this->value = $value;
    }

    public function appendValue($value)
    {
        $this->value .= $value;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function name()
    {
        return $this->name;
    }

    public function value()
    {
        $value = $this->value;
        
        if ($this->strip_tags) {
            // Remove all HTML tags (will also convert all HTML entities to feed encoding below)
            $value = trim(strip_tags($value));
        }
        
        if ($this->html_decode || $this->strip_tags) {
            // convert all HTML entities to the feed encoding
            $encoding = ($this->encoding !== null) ? $this->encoding : 'UTF-8';
            $value = html_entity_decode($value, ENT_COMPAT, $encoding);
        }
        
        return $value;
    }
    
    protected function elementMap()
    {
        return array();
    }
    
    protected function standardAttributes()
    {
        return array();
    }
    
   
    
    public function getProperty($var)
    {
        if (in_array($var, $this->standardAttributes())) {
            $method = "get" . $var;
            return $this->$method();
        } elseif (array_key_exists(strtoupper($var), $this->properties)) {
                	$prop = $this->properties[strtoupper($var)];
        	if (is_array($prop)) {
                return $prop;
            } else {
           		return $prop->value();
            }
        }
    }
    
    public function getChildElement($var)
    {
        if (array_key_exists(strtoupper($var), $this->properties)) {
            return $this->properties[strtoupper($var)];
        }
    }
 
    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        $map = $this->elementMap();
        
        if (array_key_exists($name, $map)) {
            $this->$map[$name] = $value;
        } elseif (isset($this->properties[$name])) {
            if (!is_array($this->properties[$name])) {
                $this->properties[$name] = array($this->properties[$name]);
            }
            $this->properties[$name][] = $element;
        } else {
            $this->properties[$name] = $element;
        }
    }
    
}
