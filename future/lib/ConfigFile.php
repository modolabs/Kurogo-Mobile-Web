<?php

class ConfigFile extends Config {
  protected $configs = array();
  protected $file;
  
  public function exists()
  {
    return file_exists($this->file);
  }

  // loads a config object from a file/type combination  
  public static function factory($file, $type='file', $createFile=false) {

    switch ($type)
    {
        case 'api':
        case 'web':
        case 'module':
            $pattern = sprintf("%s/%s/%%s.ini", SITE_CONFIG_DIR, $type);
            break;
        case 'site':
            $pattern = sprintf("%s/%%s.ini", SITE_CONFIG_DIR);
            break;
        case 'file':
            $pattern = "%s";
            break;
        default:
            throw new Exception("Invalid config type $type");
    }
    
    $config = new ConfigFile();
    if (!$config->loadFile(sprintf($pattern, $file), $createFile)) {
          error_log("cannot load $type configuration file: $file");
    }
    return $config;
   }

  public function addConfig(Config $config) {
       $this->configs[] = $config;
  }
   
  /* values with {XXX} in the config are replaced with other config values */
  protected function replaceCallback($matches) {
    $configs = array_merge(array($this), $this->configs);
    foreach ($configs as $config) {
        $vars = $config->getVars();
        if (isset($vars[$matches[1]])) {
            return $vars[$matches[1]];
        }
    }
    return $matches[0];
  }

  protected function loadFile($_file, $createFile=false) {
  
     if (!$file = realpath_exists($_file)) {
        if ($createFile) {
            @touch($_file);
            return $this->loadFile($_file);
        }
        return false;
     }
     
     $this->file = $file;
     
     $vars = parse_ini_file($file, false);
     $this->addVars($vars);

     $sectionVars = parse_ini_file($file, true);
     $this->addSectionVars($sectionVars);
     
     return true;
  }
  
  protected function saveValue($value) {
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
  
  public function saveFile() {

    if (!is_writable($this->file)) {
        throw new Exception("Cannot save config file: $this->file Check permissions");
    }
  
      $string = array();
      foreach ($this->sectionVars as $section=>$sectionData) {
        if (is_array($sectionData)) {
            $string[] = '';
            if (isset($sectionData[0])) {
                foreach ($sectionData as $value) {
                    $string[] = sprintf("%s[] = %s", $section, $this->saveValue($value));
                }
                $sectionData = array();
            } elseif ($section != 'No Section') {
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
      
      file_put_contents($this->file, implode(PHP_EOL, $string));
      return true;
  }

}
