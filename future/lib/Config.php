<?php

abstract class Config {
  protected $vars = array();
  protected $sectionVars = array();
  
  abstract protected function replaceCallback($matches);

  public function addVars($vars) {
    $this->vars = array_merge($this->vars, $vars);
  }
  
  /* merges together config variables by section */
  public function addSectionVars($sectionVars) {

    foreach ($sectionVars as $var=>$value) {
        if (!is_array($value)) {
            $_var = $var;
            $var = 'No Section';
            $value = array($_var=>$value);
        }
        
        if (isset($this->sectionVars[$var]) && is_array($this->sectionVars[$var])) {
            $this->sectionVars[$var] = array_merge($this->sectionVars[$var], $value);
        } else {
            $this->sectionVars[$var] = $value;
        }
    }
  }

  public function getSectionVars($expand = false) {

    if ($expand) {
        $sectionVars = $this->sectionVars;
        // flatten the "No Section" section *
        if (isset($sectionVars['No Section'])) {
            foreach ($sectionVars['No Section'] as $var=>$value) {
                $sectionVars[$var] = $value;
            }
            unset($sectionVars['No Section']);
        }
        return array_map(array($this, 'replaceVariable'), $sectionVars);
    } else {
        return $this->sectionVars;
    }
  }

  public function getVars($expand = false) {

    if ($expand) {
        return array_map(array($this, 'replaceVariable'), $this->vars);
    } else {
        return $this->vars;
    }
  }

  public function getSection($key) {

    if (isset($this->sectionVars[$key])) {
      return $this->sectionVars[$key];
    }
    
    error_log(__FUNCTION__."(): config section '$key' not set");
    
    return null;
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
  
  public function getVar($key, $expand = true) {
    if (isset($this->vars[$key])) {
        $value = $this->vars[$key];
        if ($expand) {
           $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        }
        
        return $value;
    }
    
    error_log(__FUNCTION__."(): config variable '$key' not set");
    
    return null;
  }
  
  // -------------------------------------------------------------------------
  
  protected static function getPathOrDie($path) {
    $file = realpath_exists($path);
    if (!$file) {
      die("Missing config file at '$path'");
    }
    return $file;
  }
  
  protected static function getVarOrDie($file, $vars, $key) {
    if (!isset($vars[$key])) {
      die("Missing '$key' definition in '$file'");
    }
    return $vars[$key];
  }
}
