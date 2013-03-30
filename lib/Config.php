<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * Config
 * @package Config
 */

/**
 * Config abstract class to handle config parameters
 * @package Config
 */
abstract class Config {
    const NO_EXPAND_VALUE = 0;
    const EXPAND_VALUE = 1;
    const IGNORE_CONTEXTS = 0;
    const APPLY_CONTEXTS_NAVIGATION = 1;
    const OPTION_CREATE_EMPTY=1;
    const OPTION_DO_NOT_CREATE=4;
    const OPTION_IGNORE_LOCAL=8;
    const OPTION_IGNORE_MODE=16;
    const OPTION_IGNORE_SHARED=32;
    const OPTION_IGNORE_DEFAULT=64;
    const OPTION_IGNORE_WATCHDOG=128;
    const NOT_FOUND = -1;
    
    protected $area;
    protected $type;
    protected $vars = array();
    protected $sectionVars = array();
    
    public function saveConfig() {
        throw new KurogoException("saveConfig() must be subclasses for " . get_class($this));
    }    
    
    public function __construct($area, $type) {
        $this->area = $area;
        $this->type = $type;
    }
    
    public function getType() {
        return $this->type;
    }

    public function getArea() {
        return $this->area;
    }
    
    public function addVars($vars) {
        $this->vars = array_merge($this->vars, $vars);
    }
  
    public function setVar($section, $var, $value, &$changed) {
        
        if (isset($this->sectionVars[$section])) {
            if (isset($this->sectionVars[$section][$var])) {
                if ($this->sectionVars[$section][$var] == $value) {
                    $changed = false;
                    return true;
                }                
            }
        }

        $changed = true;
        $this->sectionVars[$section][$var] = $value;
        $this->vars[$var] = $value;
        return true;
    }
    
    public function setSection($section, $values) {
        if (!is_array($values)) {
            throw new KurogoConfigurationException("Invalid values for section $section");
        }
        
        $this->sectionVars[$section] = $values;
        return true;
    }

    public function clearVar($section, $var) {
        
        if (isset($this->sectionVars[$section])) {
            if (isset($this->sectionVars[$section][$var])) {
                unset($this->sectionVars[$section][$var]);
                return true;
            }
        }
        
        return false;
    }
    
    protected function isNumeric() {
        if (isset($this->sectionVars[0])) {
            for ($i=0;$i<count($this->sectionVars);$i++) {
                if (!isset($this->sectionVars[$i])) {
                    return false;
                }
            }
            return true;
        }
        
        return false;
    }

    public function addSection($section, $vars=array()) {
        
        $this->sectionVars[$section] = $vars;
        return true;
    }

    public function removeSection($section) {
        
        if (isset($this->sectionVars[$section])) {
            
            $numeric = $this->isNumeric();
            unset($this->sectionVars[$section]);
            if ($numeric) {
                $this->sectionVars = array_values($this->sectionVars);
            }

            return true;            
        }
        
        return false;
    }
    
    public function setSectionOrder($order, &$changed) {
        if (!is_array($order)) {
            throw new KurogoConfigurationException("Invalid order array");
        }
        
        if (array_keys($this->sectionVars)==$order) {
            $changed = false;
            return true;
        }

        $sections = array();
        $numeric = $this->isNumeric();
        $i = 0;
        
        foreach ($order as $section) {
            if (isset($this->sectionVars[$section])) {
                $id = $numeric ? $i : $section;
                $sections[$id] = $this->sectionVars[$section];
            } else {
                throw new KurogoKeyNotFoundException("Can't find section $section");
            }
            $i++;
        }
         
        $this->sectionVars = $sections;
        $changed = true;
        return true;
    }

    /* used when you completely want to replace all sections */
    public function setSectionVars($sectionVars) {
        $this->sectionVars = $sectionVars;
    }
  
    /* merges together config variables by section */
    public function addSectionVars($sectionVars, $merge=true) {
        $first = true;
                
        foreach ($sectionVars as $var=>$value) {
        
            if (!is_array($value)) {
                throw new KurogoConfigurationException("Found value $var = $value that wasn't in a section. Config needs to be updated");
            }
            
            if ($merge) {
                if (isset($this->sectionVars[$var]) && is_array($this->sectionVars[$var])) {
                    $this->sectionVars[$var] = array_merge($this->sectionVars[$var], $value);
                } else {
                    $this->sectionVars[$var] = $value;
                }
            } else {
                $this->sectionVars[$var] = $value;
            }
            
            $first = false;
        }
    }

    public function getSectionVars() {
        return $this->sectionVars;
    }

    public function getVars() {
        return $this->vars;
    }

    public function getSection($key) {
        if (isset($this->sectionVars[$key])) {
            return $this->sectionVars[$key];
        } else {
            throw new KurogoKeyNotFoundException("Config section '$key' not set");
        }
    }

    public function getOptionalSection($key) {
    
        try {
            $section = $this->getSection($key);
            return $section;
        } catch (KurogoKeyNotFoundException $e) {
            return array();
        }
    }

    public function getOptionalVar($key, $default='', $section=null) {

        try {
            $value = $this->getVar($key, $section);
            return $value;
        } catch (KurogoKeyNotFoundException $e) {
            return $default;
        }
    }
  
    public function getVar($key, $section=null) {
  
        if (isset($section)) {
            if (isset($this->sectionVars[$section][$key])) {
                $value = $this->sectionVars[$section][$key];
            } else {
                throw new KurogoKeyNotFoundException("Config variable '$key' not set in section $section");
            }
        } elseif (isset($this->vars[$key])) {
            $value = $this->vars[$key];
        } else {
            throw new KurogoKeyNotFoundException("Config variable '$key' not set");
        }

        return $value;
    }
}
