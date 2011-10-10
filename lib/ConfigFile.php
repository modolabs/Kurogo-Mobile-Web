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
  const OPTION_DO_NOT_CREATE=4;
  const OPTION_IGNORE_LOCAL=8;
  const OPTION_IGNORE_MODE=16;
  protected $configs = array();
  protected $file;
  protected $type;
  protected $filepath;
  protected $localFile = false;
  protected $modeFile = false;

  protected function fileVariant($variant) 
  {
      /* valid variants are alphanumeric characters and the underscore */
      if (!preg_match("/^[a-z0-9_]+$/i", $variant)) {
        return false;
      }
      
      return $this->filepath ? substr($this->filepath, 0, -4) . '-' . $variant . substr($this->filepath, -4) : null;
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
    Kurogo::log(LOG_DEBUG, "Loading config file $file of type $type with options $options", 'config');
    $config = new ConfigFile();
    if (!($options & self::OPTION_DO_NOT_CREATE)) {
        $options = $options | self::OPTION_CREATE_WITH_DEFAULT;
    }
    
    if (!$result = $config->loadFileType($file, $type, $options)) {
        if ($options & self::OPTION_DO_NOT_CREATE) {
            return false;
        }
       throw new KurogoConfigurationException("FATAL ERROR: cannot load $type configuration file: " . self::getfileByType($file, $type));
    }

    return $config;
  }

  protected function getFileByType($file, $type)
  {
    switch ($type)
    {
        case 'site':
            $pattern = sprintf("%s/%%s.ini", SITE_CONFIG_DIR);
            break;
        case 'site-default':
            $pattern = sprintf('%s/common/config/%%s-default.ini', APP_DIR);
            break;
        case 'file':
            if ($f = realpath($file)) {
                $file = $f;
            }
            $pattern = "%s";
            break;
        case 'file-default':
            $pathinfo = pathinfo($file);
            $file = $pathinfo['filename'];
            $pattern = sprintf("%s/%%s-default.%s", $pathinfo['dirname'], $pathinfo['extension']);
            break;
        case 'project':
            $pattern = sprintf('%s/%%s.ini', MASTER_CONFIG_DIR);
            break;
        case 'project-default':
            $pattern = sprintf('%s/%%s-default.ini', MASTER_CONFIG_DIR);
            break;
        case 'theme':
            $pattern = sprintf('%s/%%s.ini', THEME_DIR);
            break;
        default:
            throw new KurogoConfigurationException("Unknown config type $type");
    }
    
    return sprintf($pattern, $file);
  }
  
  protected function createDefaultFile($file, $type)
  {
    switch ($type)
    {
        case 'file':
            $defaultFile = $this->getFileByType($file, $type.'-default');
            if (file_exists($defaultFile)) {
                $this->createDirIfNotExists(dirname($file));
                if (!is_writable(dirname($file))) {
                    throw new KurogoConfigurationException("Unable to create file $file, directory not writable");
                }
                Kurogo::log(LOG_DEBUG, "Creating default file $file from $defaultFile", 'config');
                return copy($defaultFile, $file);
            }

            throw new KurogoConfigurationException("Default file $defaultFile ($file/$type) not found");
            break;
            
        default:
            $_file = $this->getFileByType($file, $type);
            $defaultFile = $this->getFileByType($file, $type.'-default');
            
            if (file_exists($defaultFile)) {
                $this->createDirIfNotExists(dirname($_file));
                if (!is_writable(dirname($_file))) {
                    throw new KurogoConfigurationException("Unable to create " . basename($_file) . ", directory " . dirname($_file) . " not writable");
                }
                Kurogo::log(LOG_DEBUG, "Creating default file $_file from $defaultFile", 'config');
                return copy($defaultFile, $_file);
            }
            
            throw new KurogoConfigurationException("Default file $defaultFile ($file/$type) not found");
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
    
    if ($filepath = $this->loadFile($_file)) {
        $this->filepath = $filepath;
        
        if (!($options & ConfigFile::OPTION_IGNORE_MODE)) {
            if ($modeFile = $this->loadFile($this->modeFile())) {
                Kurogo::log(LOG_DEBUG, "Found " . CONFIG_MODE . " mode config file $modeFile", 'config');
                $this->moodeFile = $modeFile;
            }
        }

        if (!($options & ConfigFile::OPTION_IGNORE_LOCAL)) {
            if ($localFile = $this->loadFile($this->localFile())) {
                 Kurogo::log(LOG_DEBUG, "Found local config file $localFile", 'config');
                 $this->localFile = $localFile;
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
        Kurogo::log(LOG_DEBUG, "Creating empty config file $_file", 'config');
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
        if (!@mkdir($dir, 0700, true)) {
            throw new KurogoConfigurationException("Unable to create $dir");
        }
        Kurogo::log(LOG_DEBUG, "Created $dir", 'config');
        return true;
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
  
  /* load the actual file */
  protected function loadFile($_file) {
     if (empty($_file)) {
        return false;
     }
     
     $cacheKey = 'configfile-' . md5($_file);
     
     if ($cache = Kurogo::getCache($cacheKey)) {
        // a little sanity in case we update the structure
        if (isset($cache['vars'],$cache['sectionVars'], $cache['file'])) {
             $this->addVars($cache['vars']);
             $this->addSectionVars($cache['sectionVars']);
             return $cache['file'];
        }
     }
    
     if (!$file = realpath_exists($_file)) {
        return false;
     }
  
     $vars = parse_ini_file($file, false);
     $this->addVars($vars);

     $sectionVars = parse_ini_file($file, true);
     $this->addSectionVars($sectionVars);
     
     Kurogo::setCache($cacheKey, array(
        'vars'       => $vars,
        'sectionVars'=> $sectionVars,
        'file'       => $file
    ));

     Kurogo::log(LOG_DEBUG, "Loaded config file $file", 'config');
     return $file;
  }
  
  protected function saveValue($value) {
    $constCheck = array(
        'FULL_URL_BASE',
        'LOG_DIR',
        'CACHE_DIR',
        'LIB_DIR',
        'DATA_DIR',
        'SITE_DIR',
        'ROOT_DIR'
    );
        
    if (preg_match("/^\d+$/", $value)) {
        //it's numeric
        return $value;
    } else {

        //replace double quotes with a constant
        $value = str_replace('"', '"_QQ_"', $value);
        //quote the values
        $return = sprintf('"%s"', $value);

        //replace constants if they are there
        foreach ($constCheck as $const) {
            if (defined($const)) {
                $constValue = constant($const);
                $i = strpos($return, $constValue);
                if ($i !== false) {
                    if ($i==1) {
                        $return = $const . '"' . substr($return, $i+strlen($constValue));
                    }
                }
            }
        }
        
        return $return;
    }
  }
  
  public function saveFile() {

    if (!is_writable($this->filepath)) {
        throw new KurogoConfigurationException("Cannot save config file: $this->filepath Check permissions");
    } elseif ($this->localFile) {
        throw new KurogoConfigurationException("Safety net. File will not be saved because it was loaded and has local overrides. The code is probably wrong");
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
                    Kurogo::log(LOG_WARNING, "Error parsing non scalar value for $var in " . $this->filepath, 'config');
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
