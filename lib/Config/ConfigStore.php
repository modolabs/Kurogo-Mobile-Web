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

abstract class ConfigStore
{
    protected $configs = array();
    protected $contextData = array();
    protected $expandValue = false;
    protected $expandSection = false;
    protected $activeContexts;
    protected $site;
    
    abstract public function loadConfig($area, $type, $opts=0);
    abstract public function saveConfig(Config $config);
    abstract public function loadContextData($context, $area, $type);
 
     public function getContextData($context, $area, $type) {
        $key = $type . '-' . $area . '-' . $context;
        Kurogo::log(LOG_DEBUG,"Getting context data for $key", 'context');
        if (!isset($this->contextData[$key])) {
            if ($contextData = Kurogo::getCache("contextData-" . $key)) {
                $this->contextData[$key] = $contextData;
            } else {
                $this->contextData[$key] = $this->loadContextData($context, $area, $type);
                Kurogo::setCache("contextData-" . $key, $this->contextData[$key]);
            }
        }
        
        return $this->contextData[$key];
    }

 
    protected function getConfig($area, $type, $opts=0) {   
        if ($type instanceOf Module) {
            $key = $type->getConfigModule() . '-' . $area;
        } else {
            $key = $type . '-' . $area;
        }
        if (!isset($this->configs[$key])) {
            
            if ($config = Kurogo::getCache("config-" . $key)) {
                $this->configs[$key] = $config;
            } else {
                Kurogo::log(LOG_INFO, "Loading config $key", 'config');
                $this->configs[$key] = $this->loadConfig($area, $type, $opts);
                Kurogo::setCache("config-" . $key, $this->configs[$key]);
            }
        }
        
        return $this->configs[$key];
    }

    
    public function setSectionVars($sectionVars, $area, $type, $opts=0) {
        if ($type instanceOf Module) {
            $key = $type->getConfigModule() . '-' . $area;
        } else {
            $key = $type . '-' . $area;
        }
        if ($config = $this->getConfig($area, $type, $opts)) {
            $config->setSectionVars($sectionVars);
            $config->saveConfig();
            unset($this->configs[$key]);
            Kurogo::deleteCache('config-' . $key);
         }
    }
    
 
    public static function factory($storeClass, $args) {
        try {
            $configStore = new $storeClass();
            $configStore->init($args);
        } catch (Exception $e) {
            $configStore = null;
        }
        return $configStore;
    }
    
    protected function applyContexts($sectionVars, $area, $type, $applyContexts) {
        
        if (!$this->activeContexts) {
            $this->activeContexts = Kurogo::sharedInstance()->getActiveContexts();
        }
        
        $contextData = array();
        $deny = array();
        
        if ($type instanceOf Module) {
            $type = $type->getConfigModule();
        } 
        
        foreach ($this->activeContexts as $context) {

            if ($context->getID() == UserContext::CONTEXT_DEFAULT) {
                continue;
            }
        
            Kurogo::log(LOG_DEBUG, "Apply context $context to $area $type", 'context');
            if ($contextData) {
                throw new KurogoException("Multiple contexts not yet completed");
            }
            
            if ($contextData = $this->getContextData($context->getID(), $area, $type)) {
                
                switch ($applyContexts)
                {
                
                    case Config::APPLY_CONTEXTS_NAVIGATION:
                        $_sectionData = array();
                        $deny = array();
                        foreach ($contextData as $field=>$sections) {
                            foreach ($sections as $section=>$value) {
                                switch ($field)
                                {
                                    case 'visible':
                                        if (isset($sectionVars[$section])) {
                                            $_sectionData[$section] = $sectionVars[$section];
                                        }
                                        $_sectionData[$section][$field] = $value;
                                        break;
                                    case 'deny':
                                        if ($value) {
                                            $deny[] = $section;
                                        }
                                }
                            }
                        }
                        $sectionVars = $_sectionData;                
                        foreach ($deny as $section) {
                            unset($sectionVars[$section]);
                        }
                        break;
                    default:
                        throw new KurogoException("Invalid apply context value $applyContexts");
                }
            }
        }
        
        
        return $sectionVars;
    }

    public function getSections($area, $type, $applyContexts=Config::IGNORE_CONTEXTS) {
        $config = $this->getConfig($area, $type);
        $sectionVars = $config->getSectionVars();
        if ($applyContexts != Config::IGNORE_CONTEXTS) {    
            $sectionVars = $this->applyContexts($sectionVars, $area, $type, $applyContexts);
        }
        
        return $sectionVars;
    }

    public function getOptionalSections($area, $type, $applyContexts=Config::IGNORE_CONTEXTS) {
        $sectionVars = array();
        if ($config = $this->getConfig($area, $type, Config::OPTION_DO_NOT_CREATE)) {
            $sectionVars = $config->getSectionVars();
        }

        if ($applyContexts != Config::IGNORE_CONTEXTS) {    
            $sectionVars = $this->applyContexts($sectionVars, $area, $type, $applyContexts);
        }
        return $sectionVars;
    }

    public function getSection($section, $area, $type) {
        $config = $this->getConfig($area, $type);
        $sectionVars = $config->getSection($section);
        if ($this->expandSection) {
            $sectionVars = array_map(array($this, 'replaceVariable'), $sectionVars);
        }
        return $sectionVars;

    }

    public function getOptionalSection($section, $area, $type) {
        $sectionVars = array();
        if ($config = $this->getConfig($area, $type, Config::OPTION_DO_NOT_CREATE)) {
            $sectionVars = $config->getOptionalSection($section);
        }

        if ($sectionVars && $this->expandSection) {
            $sectionVars = array_map(array($this, 'replaceVariable'), $sectionVars);
        }
        return $sectionVars;
    }

    public function getVar($var, $section, $area, $type) {
        $config = $this->getConfig($area, $type);
        $value = $config->getVar($var, $section);
        
        if ($this->expandValue) {
           $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        }
        
        return $value;
    }

    public function getOptionalVar($var, $default, $section, $area, $type) {
        $value = $default;
        if ($config = $this->getConfig($area, $type, Config::OPTION_DO_NOT_CREATE)) {
            $value =  $config->getOptionalVar($var, $default, $section);
        }

        if ($this->expandValue) {
           $value = preg_replace_callback('/\{([A-Za-z_]+)\}/', array($this, 'replaceCallback'), $value);
        }
        return $value;
    }


    protected function init($args) {
        if (isset($args['site']) && $args['site'] instanceOf KurogoSite) {
            $this->site = $args['site'];
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
 
  /* values with {XXX} in the config are replaced with other config values */
  protected function replaceCallback($matches) {
    foreach ($this->configs as $config) {
        $vars = $config->getVars();
        if (isset($vars[$matches[1]])) {
            return $vars[$matches[1]];
        }
    }
    return $matches[0];
  }

}