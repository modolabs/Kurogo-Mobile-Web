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
 * @package Config
 */

class ConfigFileStore extends ConfigStore
{
    protected $configs = array();

    public function loadConfig($area, $type, $options=0) {
        
        $module = null;
        if ($type instanceOf Module) {
            $module = $type;
            $type = $type->getConfigModule();
        }
        $key = $type . '-' . $area . '-' . $options;
        
        if (isset($this->configs[$key])) {
            return $this->configs[$key];
        }

        Kurogo::log(LOG_DEBUG, "Loading config file $area of type $type with options $options", 'config');
        if ($type == 'file') {
            $options = $options | Config::OPTION_IGNORE_DEFAULT;
        }

        if (!$config = $this->loadConfigFile($area, $type, $module, $options)) {
            if ($options & Config::OPTION_DO_NOT_CREATE) {
                return false;
            }
            throw new KurogoConfigurationException("ConfigFile: cannot load $type configuration file $area");
        }

        return $config;
    }
    
    public function loadContextData($context, $area, $type) {
        $key = $type . '-' . $area;
        if (!isset($this->configs[$key])) {
            $opts = Config::OPTION_DO_NOT_CREATE;
            $this->configs[$key] = $this->loadConfig($area, $type, $opts);
        }
        
        if ($this->configs[$key]) {
            return $this->configs[$key]->getContextData($context);
        }
    }

  protected function getFileByType($area, $type, $module=null)
  {
    switch ($type)
    {
        case 'site':
            if ($this->site) {
                $pattern = sprintf("%s/%%s.ini", $this->site->getConfigDir());
            } else {
                throw new KurogoException("Attempting to get site config before site is instantiated");
            }
            break;
        case 'site-default':
            $pattern = sprintf('%s/common/config/%%s.ini', APP_DIR);
            break;
        case 'site-shared':
            $pattern = sprintf("%s/%%s.ini", $this->site->getSharedConfigDir());
            break;
        case 'file':
            if ($f = realpath($area)) {
                $area = $f;
            }
            $pattern = "%s";
            break;
        case 'file-shared':
        	return null;
            break;
        default:
        
            if (preg_match("/(.*?)-default$/", $type, $bits)) {
                $type = $bits[1];

                /* Make sure we use the id of the parent module */
                if ($module) {
                    $type = $module->getID();
                }
                $files = array( 
                    sprintf('%s/%s/config/%s.ini', $this->site->getSiteModulesDir(), $type, $area),
                    sprintf('%s/%s/config/%s.ini', $this->site->getSharedModulesDir(), $type, $area),
                    sprintf('%s/%s/config/%s.ini', MODULES_DIR, $type, $area),
                    sprintf('%s/common/config/module/%s.ini', APP_DIR, $area)
                );
        
                foreach ($files as $area) {
                    if (is_file($area)) {
                        return $area;
                    }
                }
        
                return false;
            } elseif (preg_match("/(.*?)-shared$/", $type, $bits)) {
                $type = $bits[1];
                $pattern = sprintf('%s/%s/%%s.ini',$this->site->getSharedConfigDir(), $type);
            } else {
                if ($this->site) {
                    $pattern = sprintf('%s/%s/%%s.ini', $this->site->getConfigDir(), $type);
                } else {
                    throw new KurogoException("Attempting to get site $type config before site is instantiated");
                }
            }
    }
    
    return sprintf($pattern, $area);
  }

  public static function fileVariant($file, $variant) 
  {
      /* valid variants are alphanumeric characters and the underscore */
      if (!preg_match("/^[a-z0-9_]+$/i", $variant)) {
        return false;
      }
      
      $result = $file ? substr($file, 0, -4) . '-' . $variant . substr($file, -4) : null; 
      return $result;
  }

  public function saveConfig(Config $config) {
    $key = sprintf("%s-%s", $config->getType(), $config->getArea());

    if (!$file = $config->getFile('base')) {
        throw new KurogoConfigurationException("Unable to load base file for " . $config->getType() . '-' . $config->getArea());
    }

    if (count($config->getFiles())>1) {
        KurogoDebug::Debug($config, true);
        throw new KurogoConfigurationException("Safety net. File will not be saved because it was loaded with extra files. The code is probably wrong");
    }

    $data = $config->getSaveData();

    if (!is_writable($file)) {
        throw new KurogoConfigurationException("Cannot save config file: $file Check permissions");
    }

    file_put_contents($file, $data);
    unset($this->configs[$key]);
    Kurogo::deleteCache('config-' . $key);
    return true;
  }

  protected function loadConfigFile($area, $type, $module, $options)
  {
    if (!$_file = $this->getFileByType($area, $type, $module)) {
        return false;
    }

    Kurogo::log(LOG_DEBUG, "Loading config file $area of type $type with options $options", 'config');
    if ($type == 'file') {
        $options = $options | Config::OPTION_IGNORE_DEFAULT;
    }
    
    $config = new ConfigFile($area, $type);
    if ($module) {
        //$config->setModuleID($module->getID());
    }


    
	if (!($options & Config::OPTION_IGNORE_DEFAULT)) {
	    $config->setFile('default', $this->getFileByType($area, $type . '-default', $module));
	}

	if (!($options & Config::OPTION_IGNORE_SHARED)) {
	    $config->setFile('shared', $this->getFileByType($area, $type . '-shared', $module));
	}
    
    if ($config->setFile('base', $_file)) {
        
        if (!($options & Config::OPTION_IGNORE_MODE)) {
            $modes = Kurogo::getConfigModes();
            foreach ($modes as $mode) {
                $config->setFile($mode, self::fileVariant($_file, $mode));
            }
        }

        if (!($options & Config::OPTION_IGNORE_LOCAL)) {
            $config->setFile('local', self::fileVariant($_file, 'local'));
        }

        return $config;
    } elseif (count($config->getFiles()) > 0) {
    	return $config;
    }
    
    if ($options & Config::OPTION_CREATE_EMPTY) {
        if (!is_dir(dirname($_file))) {
            throw new KurogoConfigurationException("Directory " . dirname($_file) . " does not exist");
        }
        Kurogo::log(LOG_DEBUG, "Creating empty config file $_file", 'config');
        @touch($_file);
        if ($config->setFile('base', $_file)) {
            return $config;
        }    
    }
    
    return false;
    
  }
  

}