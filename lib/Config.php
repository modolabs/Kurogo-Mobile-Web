<?php

require('Config/ConfigFile.php');
require('Config/ConfigGroup.php');
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
    protected $vars = array();
    protected $sectionVars = array();
  
    abstract protected function replaceCallback($matches);

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
                throw new KurogoConfigurationException("Can't find section $section");
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

    public function getSectionVars($opts = Config::NO_EXPAND_VALUE) {

        if ($opts & Config::EXPAND_VALUE) {
            $sectionVars = $this->sectionVars;
            return array_map(array($this, 'replaceVariable'), $sectionVars);
        } else {
            return $this->sectionVars;
        }
    }

    public function getVars($opts = Config::NO_EXPAND_VALUE) {
        if ($opts & Config::EXPAND_VALUE) {
            return array_map(array($this, 'replaceVariable'), $this->vars);
        } else {
            return $this->vars;
        }
    }

    public function getSection($key) {
        if (isset($this->sectionVars[$key])) {
            return $this->sectionVars[$key];
        } else {
            throw new KurogoConfigurationException("Config section '$key' not set");
        }
    }

    public function getOptionalSection($key) {
    
        try {
            $section = $this->getSection($key);
            return $section;
        } catch (KurogoConfigurationException $e) {
            return array();
        }
    }

    /* values with {XXX} in the config are replaced with other config values */
    protected function replaceVariable($value) {
    
        if (is_scalar($value)) {
            $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        } else {
            $value = array_map(array($this, 'replaceVariable'), $value);
        }
    
        return $value;
    }

    public function getOptionalVar($key, $default='', $section=null, $opts = Config::EXPAND_VALUE) {

        try {
            $value = $this->getVar($key, $section, $opts);
            return $value;
        } catch (KurogoConfigurationException $e) {
            return $default;
        }
    }
  
    public function getVar($key, $section=null, $opts = Config::EXPAND_VALUE) {
  
        if (!is_null($section)) {
            if (isset($this->sectionVars[$section][$key])) {
                $value = $this->sectionVars[$section][$key];
            } else {
                throw new KurogoConfigurationException("Config variable '$key' not set in section $section");
            }
        } elseif (isset($this->vars[$key])) {
            $value = $this->vars[$key];
        } else {
            throw new KurogoConfigurationException("Config variable '$key' not set");
        }

        if ($opts & Config::EXPAND_VALUE) {
           $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        }
        
        return $value;
    }
}