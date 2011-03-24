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
  const LOG_ERRORS = 2;
  const SUPRESS_ERRORS = 0;
  protected $vars = array();
  protected $sectionVars = array();
  
  abstract protected function replaceCallback($matches);

  public function addVars($vars) {
    $this->vars = array_merge($this->vars, $vars);
  }

  /* used when you completely want to replace all sections */
  public function setSectionVars($sectionVars)
  {
     $this->sectionVars = $sectionVars;
  }
  
  /* merges together config variables by section */
  public function addSectionVars($sectionVars, $merge=true) {
    $first = true;
    
    foreach ($sectionVars as $var=>$value) {
        if (!is_array($value)) {
            $_var = $var;
            $var = 'No Section';
            $value = array($_var=>$value);
            if ($first && !$merge) {
                $this->sectionVars['No Section'] = array();
            }
        }
        
        if ($merge || $var=='No Section') {
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

  public function getVars($opts = Config::NO_EXPAND_VALUE) {
    if ($opts & Config::EXPAND_VALUE) {
        return array_map(array($this, 'replaceVariable'), $this->vars);
    } else {
        return $this->vars;
    }
  }

  public function getSection($key, $opts=Config::LOG_ERRORS) {
    if (strlen($key)==0) {
        $key = 'No Section';
    }

    if (isset($this->sectionVars[$key])) {
      return $this->sectionVars[$key];
    }
    
    if ($opts & Config::LOG_ERRORS) {
        $bt = debug_backtrace();
        $call = sprintf("%s:%s", $bt[0]['file'], $bt[0]['line']);
        error_log(__FUNCTION__."(): config section '$key' not set (Called $call)");
    }
    
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
  
  public function getVar($key, $opts = Config::EXPAND_VALUE) {
  
    if (isset($this->vars[$key])) {
        $value = $this->vars[$key];
        if ($opts & Config::EXPAND_VALUE) {
           $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        }
        
        return $value;
    }
    
    if ($opts & Config::LOG_ERRORS) {
        $bt = debug_backtrace();
        $call = sprintf("%s:%s", $bt[0]['file'], $bt[0]['line']);
        error_log(__FUNCTION__."(): config variable '$key' not set (Called $call)");
    }
    
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
