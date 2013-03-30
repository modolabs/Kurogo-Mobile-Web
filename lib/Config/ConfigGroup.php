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
 
/**
 * Handles multiple config files
 * @package Config
 */
class ConfigGroup extends Config
{
    protected $configs = array();

    // return the most recently updated config file    
    public function getLastModified() {
        $lastModified = null;
        foreach ($this->configs as $config) {
            $configModified = $config->getLastModified();
            if ($configModified > $lastModified) {
                $lastModified = $configModified;
            }
        }
        return $lastModified;
    }

    public function addConfig(Config $config)
    {
       $this->configs[] = $config;
       //$config->addConfig($this);
       $this->addVars($config->getVars());
       $this->addSectionVars($config->getSectionVars());
    }
    
}
