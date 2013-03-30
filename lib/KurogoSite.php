<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoSite
{
    protected $name;
    protected $title;
    protected $siteDir;
    protected $initArgs;
    protected $urlBase='/';
    protected $urlBaseAuto=true;
    protected $host;
    protected $default = false;
    protected $enabled = true;
    protected $configStore;
    protected $configMode='';
    protected $cacher;
    protected $requiresSecure = false;
    const EXACT_MATCH=3;
    const DEFAULT_MATCH=2;
    const INEXACT_MATCH=1;
    const NO_MATCH=0;
    
    public function __construct($name, $config) {
        $config = is_array($config) ? $config : array();
        
        if (!self::isValidSiteName($name)) {
            throw new KurogoException("Invalid site name $name");
        }

        $this->name = $name;

        if (isset($config['SITE_DIR'])) {
            $this->siteDir = $config['SITE_DIR'];
        } else {
            $this->siteDir = SITES_DIR . DIRECTORY_SEPARATOR . $name;
        }

        if (isset($config['urlBase'])) {
            //trim all slashes
            $urlBase = trim(strtolower($config['urlBase']),'/');
            if (strlen($urlBase)>0 && !preg_match("#^[a-z0-9_/.-]+$#i", $urlBase)) {
                throw new KurogoConfigurationException("Invalid urlBase " . $config['urlBase']);
            }
            $this->urlBase = '/' . $urlBase;
            $this->urlBaseAuto = false;
        }

        if (isset($config['host'])) {
            if (!preg_match("!^([a-z0-9.-]+)(:\d+)?$!i", $config['host'])) {
                throw new KurogoConfigurationException('Invalid host "' . $config['host'] . '"');
            }
            $this->host = $config['host'];
        }

        if (isset($config['title'])) {
            $this->title = $config['title'];
        } else {
            $this->title = $this->name;
        }

        if (isset($config['CONFIG_MODE'])) {
            $this->configMode = $config['CONFIG_MODE'];
        }

        $this->enabled = (bool) Kurogo::arrayVal($config, 'enabled', true);

        if (isset($config['default'])) {
            $this->default = (bool) $config['default'];
        }
        
        if (isset($config['secure'])) {
            $this->requiresSecure = (bool) $config['secure'];
        }
        
        $this->initArgs = $config;
    }
    
    public function getRequiresSecure() {
        return $this->requiresSecure || $this->getOptionalSiteVar('SECURE_REQUIRED');
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function isDefault() {
        return $this->default;
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function getConfigMode() {
        return $this->configMode;
    }

    public static function isValidSiteName($name) {
        return preg_match("/^[a-z][a-z0-9_-]*$/i", $name);
    }
    
    public function getName() {
        return $this->name;
    }

    public function getURLBaseAuto() {
        return $this->urlBaseAuto;
    }

    public function getHost() {
        if(!empty($this->host)) {
            return $this->host;
        } else {
            return SERVER_HOST;
        }
    }

    public function getHostValue() {
        return $this->host;
    }

    protected function replaceDir($dir) {
        static $hostname;
        if (strlen($hostname)==0) {
            $hostname = gethostname();
        }
        return str_replace(array('%SITE_DIR%','%SITES_DIR%','%HOSTNAME%'), array($this->siteDir, SITES_DIR, $hostname), $dir);
    }

    public function getSiteDir() {
        return $this->siteDir;
    }

    public function getSharedDir() {
        return $this->replaceDir(Kurogo::arrayVal($this->initArgs, 'SHARED_DIR', SITES_DIR . DIRECTORY_SEPARATOR . 'shared'));
    }

    public function getSharedConfigDir() {
        return $this->getSharedDir() . DIRECTORY_SEPARATOR . 'config';
    }

    public function getSharedAppDir() {
        return $this->getSharedDir() . DIRECTORY_SEPARATOR . 'app';
    }

    public function getSharedModulesDir() {
        return $this->getSharedAppDir() . DIRECTORY_SEPARATOR . 'modules';
    }

    public function getSharedDataDir() {
        return $this->getSharedDir() . DIRECTORY_SEPARATOR . 'data';
    }

    public function getSharedLibDir() {
        return $this->getSharedDir() . DIRECTORY_SEPARATOR . 'lib';
    }

    public function getSharedScriptsDir() {
        return $this->getSharedDir() . DIRECTORY_SEPARATOR . 'scripts';
    }

    public function getBaseCacheDir() {
        return $this->replaceDir(Kurogo::arrayVal($this->initArgs, 'BASE_CACHE_DIR', $this->siteDir . DIRECTORY_SEPARATOR . 'cache'));
    }

    public function getCacheDir() {
        return $this->replaceDir(Kurogo::arrayVal($this->initArgs, 'CACHE_DIR', $this->siteDir . DIRECTORY_SEPARATOR . 'cache'));
    }

    public function getBaseLogDir() {
        return $this->replaceDir(Kurogo::arrayVal($this->initArgs, 'BASE_LOG_DIR', $this->siteDir . DIRECTORY_SEPARATOR . 'logs'));
    }
    
    public function getLogDir() {
        return $this->replaceDir(Kurogo::arrayVal($this->initArgs, 'LOG_DIR', $this->siteDir . DIRECTORY_SEPARATOR . 'logs'));
    }
    
    public function getSiteLibDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . 'lib';
    }

    public function getSiteAppDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . 'app';
    }

    public function getSiteModulesDir() {
        return $this->getSiteAppDir() . DIRECTORY_SEPARATOR . 'modules';
    }
    
    public function getDataDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . 'data';
    }

    public function getWebBridgeDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . KurogoWebBridge::getAssetsDir();
    }

    public function getConfigDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . 'config';
    }

    public function getConfigDisabledDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . 'config_disabled';
    }

    public function getScriptsDir() {
        return $this->siteDir . DIRECTORY_SEPARATOR . 'scripts';
    }
    
    public function getConfigStore() {
        if (!$this->configStore) {
            $args = array('site'=>$this);
            $configStoreClass = Kurogo::arrayVal($this->initArgs, 'CONFIG_CLASS', 'ConfigFileStore');
            $this->configStore = ConfigStore::factory($configStoreClass, $args);
        }
        return $this->configStore;
    }
    
    public function cacher() {
        if (!isset($this->cacher)) {
            if ($cacheClass = Kurogo::arrayVal($this->initArgs, 'CACHE_CLASS')) {
                $this->cacher = KurogoMemoryCache::factory($cacheClass, $this->initArgs);
            } else {
                $this->cacher = false;
            }
        } 
        
        return $this->cacher;
    }
    
    // returns a score based on the path/host
    public function getSiteScore($path, $host) {
    
        // if the urlBase is '/' then treat it as empty
        $urlBase = $this->urlBase == '/' ? '' : $this->urlBase;
        
        // get the first path component
        if (strlen($path)>1 && ($offset = strpos($path, '/', 1)) !== false) {
            $path = substr($path, 0, $offset);
        }
        
        $score = 0;
        //if the url base matches
        if ($urlBase == $path) {
            $score+=2;
        }
        
        if ($this->host == $host) {
            $score+=3;
        }

        if ($this->default) {
            $score+=1;
        }
        
        return $score;
    }
    
    public function getURL() {
        $url = sprintf("http%s://%s%s", $this->getRequiresSecure() ? 's' : '', $this->getHost(), $this->getUrlBase());
        return $url;
    }
    
    public function getURLBase() {
        return $this->urlBase;
    }

    public function getSiteSections($area, $applyContexts=Config::IGNORE_CONTEXTS) {
        return $this->getConfigStore()->getSections($area, 'site', $applyContexts);
    }

    public function getOptionalSiteSections($area, $applyContexts=Config::IGNORE_CONTEXTS) {
        return $this->getConfigStore()->getOptionalSections($area, 'site', $applyContexts);
    }

    public function getSiteSection($section, $area='site') {
        return $this->getConfigStore()->getSection($section, $area, 'site');
    }

    public function getOptionalSiteSection($section, $area='site') {
        return $this->getConfigStore()->getOptionalSection($section, $area, 'site');
    }

    public function getSiteVar($var, $section=null, $area='site') {
        return $this->getConfigStore()->getVar($var, $section, $area, 'site');
    }

    public function getOptionalSiteVar($var, $default='', $section=null, $area='site') {
        return $this->getConfigStore()->getOptionalVar($var, $default, $section, $area, 'site');
    }
}
