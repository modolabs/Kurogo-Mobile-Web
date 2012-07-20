<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * @package Config
 */

/**
 * Class to load and parse ini files for modules
 * @package Config
 */
class ModuleConfigFile extends ConfigFile {
    protected $module;

    // loads a config object from a file/type combination  
    public static function factory($configModule, $type='file', $options=0) {
        $args = func_get_args();
        $module = isset($args[3]) ? $args[3] : null;
        $config = new ModuleConfigFile();
        if ($module) {
            $config->setModule($module);
        }
        
        if (!($options & self::OPTION_DO_NOT_CREATE)) {
            $options = $options | self::OPTION_CREATE_WITH_DEFAULT;
        }
        
        if (!$result = $config->loadFileType($configModule, $type, $options)) {
            if ($options & self::OPTION_DO_NOT_CREATE) {
                return false;
            }
            throw new KurogoConfigurationException("FATAL ERROR: cannot load $type configuration file for module $configModule");
        }
        
        return $config;
    }
  
    protected function cacheKey() {
        return "config-module-{$this->file}-{$this->type}";
    }
    
    public function setModule(Module $module) {
        $this->module = $module;
    }
    
    protected function getFileByType($id, $type)
    {
        if (preg_match("/-default$/", $type)) {

            /* Make sure we use the id of the parent module */
            if ($this->module) {
                $id = $this->module->getID();
            }
            $files = array( 
                sprintf('%s/%s/config/%s.ini', SITE_MODULES_DIR, $id, $type),
                sprintf('%s/%s/config/%s.ini', MODULES_DIR, $id, $type),
                sprintf('%s/common/config/%s.ini', APP_DIR, $type)
            );
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    return $file;
                }
            }
            
            throw new KurogoConfigurationException("Unable to find $type config file for module $id");
        } elseif (preg_match("/(.*?)-shared$/", $type, $bits)) {
            $file = sprintf('%s/%s/%s.ini', SHARED_CONFIG_DIR, $id, $bits[1]);
        } else {
            $file = sprintf('%s/%s/%s.ini', SITE_CONFIG_DIR, $id, $type);
        }
        
        return $file;
    }
    

}
