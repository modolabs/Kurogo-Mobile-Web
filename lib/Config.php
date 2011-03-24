<?php
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

    /* used when you completely want to replace all sections */
    public function setSectionVars($sectionVars) {
        $this->sectionVars = $sectionVars;
    }
  
    /* merges together config variables by section */
    public function addSectionVars($sectionVars, $merge=true) {
        $first = true;
                
        foreach ($sectionVars as $var=>$value) {
        
            if (!is_array($value)) {
                throw new Exception("Found value that wasn't in a section. Config needs to be updated");
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
            throw new Exception("Config section '$key' not set");
        }
    }

    public function getOptionalSection($key) {
    
        try {
            $section = $this->getSection($key);
            return $section;
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            return $default;
        }
    }
  
    public function getVar($key, $section=null, $opts = Config::EXPAND_VALUE) {
  
        if (!is_null($section)) {
            if (isset($this->sectionVars[$section][$key])) {
                $value = $this->sectionVars[$section][$key];
            } else {
                throw new Exception("Config variable '$key' not set in section $section");
            }
        } elseif (isset($this->vars[$key])) {
            $value = $this->vars[$key];
        } else {
            throw new Exception("Config variable '$key' not set");
        }

        if ($opts & Config::EXPAND_VALUE) {
           $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        }
        
        return $value;
    }
}