<?php

class Config {
  protected $configs = array();
  protected $vars = array();
  protected $sectionVars = array();
  protected $files=array();

  // loads a config object from a file/type combination  
  public static function factory($file, $type='file', $ignoreError=false)
  {
    switch ($type)
    {
        case 'api':
        case 'web':
            $pattern = sprintf("%s/%s/%%s.ini", SITE_CONFIG_DIR, $type);
            break;
        case 'file':
            $pattern = "%s";
            break;
    }
    
    $config = new Config();
    if (!$config->loadFile(sprintf($pattern, $file))) {
        if (!$ignoreError) {
          error_log("ccannot load $type configuration file: $file");
        }
    }
    return $config;
  }
  
  protected function addConfig(Config $config)
  {
    $this->configs[] = $config;
  }
  
  protected function addVars($vars)
  {
    $this->vars = array_merge($this->vars, $vars);
  }
  
  protected function saveValue($value)
  {
    $constCheck = array(
        'FULL_URL_BASE'=>FULL_URL_BASE,
        'LOG_DIR'=>LOG_DIR,
        'CACHE_DIR'=>CACHE_DIR,
        'DATA_DIR'=>DATA_DIR,
        'SITE_DIR'=>SITE_DIR,
        'ROOT_DIR'=>ROOT_DIR
    );
        
    if (preg_match("/^\d+$/", $value)) {
        //it's numeric
        return $value;
    } elseif (strpos($value, '"')!==false) {
        //not sure what to do if there is a double quote
        trigger_error("Double quote found in $value", E_USER_ERROR);
    } else {
        //quote the values
        $return = sprintf('"%s"', $value);

        //replace constants if they are there
        foreach ($constCheck as $const=>$constValue) {
            $i = strpos($return, $constValue);
            if ($i !== false) {
                if ($i==1) {
                    $return = $const . '"' . substr($return, $i+strlen($constValue));
                }
            }
        }
        
        return $return;
    }
  }
  
  public function saveConfig()
  {
      foreach ($this->files as $file=>$data) {
          $string = array();
          foreach ($data['sectionVars'] as $section=>$sectionData) {
            if (is_array($sectionData)) {
                $string[] = '';
                if (isset($sectionData[0])) {
                    foreach ($sectionData as $value) {
                        $string[] = sprintf("%s[] = %s", $section, $this->saveValue($value));
                    }
                    $sectionData = array();
                } else {
                    $string[] = sprintf("[%s]", $section);
                }

                foreach ($sectionData as $var=>$value) {
                    if (is_scalar($value)) {
                        $string[] = sprintf('%s = %s', $var, $this->saveValue($value));
                    } elseif (isset($value[0])) {
                        foreach ($value as $_value) {
                            $string[] = sprintf("%s[] = %s", $var, $this->saveValue($_value));
                        }
                    } else {
                        trigger_error("Error parsing non scalar value in " . $data['file'], E_USER_ERROR);
                    }
                }
            } else {
                $string[] = sprintf('%s = %s', $section, $this->saveValue($sectionData));
            }
        
         }
         
         echo $data['file'] . PHP_EOL . implode(PHP_EOL, $string) . "\n<br><br>";
      }
  }
  

  /* merges together config variables by section */
  protected function addSectionVars($sectionVars) {
    foreach ($sectionVars as $var=>$value) {
        
        if (isset($this->sectionVars[$var]) && is_array($this->sectionVars[$var])) {
            $this->sectionVars[$var] = array_merge($this->sectionVars[$var], $value);
        } else {
            $this->sectionVars[$var] = $value;
        }
    }
  }

  protected function loadFile($_file) {
  
     if (!$file = realpath_exists($_file)) {
        return false;
     }
     
     if (in_array($file, $this->files)) {
        return;
     }
     
     $config = array(
        'file'=>$file,
        'vars'=>parse_ini_file($file, false),
        'sectionVars'=>parse_ini_file($file, true)
     );
          
     $this->files[] = $config;

     $this->addVars($config['vars']);
     $this->addSectionVars($config['sectionVars']);
     return true;
  }

  public function getSectionVars($expand = false)
  {
    if ($expand) {
       return array_map(array($this, 'replaceVariable'), $this->sectionVars);
    } else {
        return $this->sectionVars;
    }
  }

  public function getVars($expand = false)
  {
    if ($expand) {
        return array_map(array($this, 'replaceVariable'), $this->vars);
    } else {
        return $this->vars;
    }
  }

  public function getSection($key)
  {
    if (isset($this->sectionVars[$key])) {
      return $this->sectionVars[$key];
    }
    
    error_log(__FUNCTION__."(): config section '$key' not set");
    
    return null;
  }

  protected function replaceVariable($value)
  {
      if (is_scalar($value)) {
         $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
      } else {
        $value = array_map(array($this, 'replaceVariable'), $value);
      }
      return $value;
  }
  
  protected function replaceCallback($matches)
  {
    $configs = array_merge(array($this), $this->configs);
    foreach ($configs as $config) {
        $vars = $config->getVars();
        if (isset($vars[$matches[1]])) {
            return $vars[$matches[1]];
        }
    }
    return $matches[0];
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
