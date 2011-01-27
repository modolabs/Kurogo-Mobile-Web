<?php
/**
 * @package Config
 */

/**
 * Class to load and parse ini files
 * @package Config
 */
class ConfigFile extends Config {
  const OPTION_CREATE_EMPTY=1;
  const OPTION_CREATE_WITH_DEFAULT=2;
  const OPTION_DIE_ON_FAILURE=4;
  const OPTION_IGNORE_LOCAL=8;
  const OPTION_IGNORE_MODE=16;
  protected $configs = array();
  protected $file;
  protected $type;
  protected $filepath;
  protected $localFile = false;

  protected function fileVariant($variant) 
  {
      /* valid variants are alphanumeric characters and the underscore */
      if (!preg_match("/^[a-z0-9_]+$/i", $variant)) {
        return false;
      }
      
      if (preg_match("/^(.*?)\.ini$/", $this->filepath, $bits)) {      
         return realpath_exists(sprintf("%s-%s.ini", $bits[1], $variant));
      }
  }
  
  public function modeFile()
  {
    return $this->fileVariant(CONFIG_MODE);
  }

  public function localFile()
  {
    return CONFIG_IGNORE_LOCAL ? false : $this->fileVariant('local');
  }

  // loads a config object from a file/type combination  
  public static function factory($file, $type='file', $options=0) {
    $config = new ConfigFile();
    
    if (!$result = $config->loadFileType($file, $type, $options)) {
        if ($options & ConfigFile::OPTION_DIE_ON_FAILURE) {
          die("FATAL ERROR: cannot load $type configuration file: $file");
        }
    }
    
    return $config;
  }

  protected function getFileByType($file, $type)
  {
    switch ($type)
    {
        case 'site-default':
            $pattern = sprintf('%s/%%s-default.ini', MASTER_CONFIG_DIR);
            break;
        case 'module-default':
            $pattern = sprintf('%s/%%1$s/config/%%1$s-default.ini', MODULES_DIR);
            break;
        case 'api':
        case 'web':
        case 'module':
        case 'page':
        case 'feeds':
            $pattern = sprintf("%s/%s/%%s.ini", SITE_CONFIG_DIR, $type);
            break;
        case 'site':
            $pattern = sprintf("%s/%%s.ini", SITE_CONFIG_DIR);
            break;
        case 'file':
            if ($f = realpath($file)) {
                $file = $f;
            }
            $pattern = "%s";
            break;
        default:
            return false;
    }
    
    return sprintf($pattern, $file);
  }
  
  protected function createDefaultFile($file, $type)
  {
    switch ($type)
    {
        case 'site':
            $_file = $this->getFileByType($file, $type);
            $defaultFile = $this->getFileByType($file, $type.'-default');
            if (file_exists($defaultFile)) {
                $this->createDirIfNotExists(dirname($_file));
                return @copy($defaultFile, $_file);
            }
            
            return false;
            break;
            
        case 'module':
            //check to see if the module has a default config file first
            $_file = $this->getFileByType($file, $type);
            $defaultFile = $this->getFileByType($file, $type.'-default');
            if (file_exists($defaultFile)) {
                $this->createDirIfNotExists(dirname($_file));
                return @copy($defaultFile, $_file);
            } elseif ($module = Module::factory($file)) {
                return $module->createDefaultConfigFile();
            } else {
                throw new Exception("Module $file not found");
            }
            break;
    }
  }
  
  protected function loadFileType($file, $type, $options=0)
  {
    $this->file = $file;
    $this->type = $type;

    if (!$_file = $this->getFileByType($file, $type)) {
        return false;
    }
    
    if ($this->loadFile($_file)) {
        if (!($options & ConfigFile::OPTION_IGNORE_MODE)) {
            if ($modeFile = $this->modeFile()) {
                 $this->modeFile = $modeFile;
                 $vars = parse_ini_file($modeFile, false);
                 $this->addVars($vars);
                 $sectionVars = parse_ini_file($modeFile, true);
                 $this->addSectionVars($sectionVars);
            }
        }
        if (!($options & ConfigFile::OPTION_IGNORE_LOCAL)) {
            if ($localFile = $this->localFile()) {
                 $this->localFile = $localFile;
                 $vars = parse_ini_file($localFile, false);
                 $this->addVars($vars);
                 $sectionVars = parse_ini_file($localFile, true);
                 $this->addSectionVars($sectionVars);
            }
        }
        return true;
    } 
    
    if ($options & ConfigFile::OPTION_CREATE_EMPTY) {
        //create an empty file and then load it
        $this->createDirIfNotExists(dirname($_file));
        if (!is_dir(dirname($_file))) {
            @mkdir(dirname($_file), 0700, true);
        }
         @touch($_file);
         return $this->loadFile($_file);
    } elseif ($options & ConfigFile::OPTION_CREATE_WITH_DEFAULT) {
        //attempt to create a file with default options
        if ($this->createDefaultFile($file, $type)) {
            return $this->loadFile($_file);
        }
    }
    
    return false;
    
  }
  
  private function createDirIfNotExists($dir)
  {
    if (!is_dir($dir)) {
        return @mkdir($dir, 0700, true);
    }
    
    return true;
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

  protected function loadFile($_file) {
  
     if (!$file = realpath_exists($_file)) {
        return false;
     }
     
     $this->filepath = $file;
     
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

    if (!is_writable($this->filepath)) {
        throw new Exception("Cannot save config file: $this->filepath Check permissions");
    } elseif ($this->localFile) {
        throw new Exception("Safety net. File will not be saved because it was loaded and has local overrides. The code is probably wrong");
    }
  
      $string = array();
      foreach ($this->sectionVars as $section=>$sectionData) {
        if (is_array($sectionData)) {
            if (count($string)>0) {
                $string[] = '';
            }
            if (isset($sectionData[0])) {
                foreach ($sectionData as $value) {
                    $string[] = sprintf("%s[] = %s", $section, $this->saveValue($value));
                }
                $sectionData = array();
            } elseif ($section !== 'No Section') {
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
                    trigger_error("Error parsing non scalar value for $var in " . $this->filepath, E_USER_ERROR);
                }
            }
        } else {
            $string[] = sprintf('%s = %s', $section, $this->saveValue($sectionData));
        }
        
      }
      
      file_put_contents($this->filepath, implode(PHP_EOL, $string));
      return true;
  }

}
