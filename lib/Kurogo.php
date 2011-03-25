<?php

class Kurogo
{
    public static function getLanguages() {
        return array(
            'en'=>'English'
        );
    }
    
    public static function isNotEmptyString($val) {
        return strlen($val)>0;
    }
    
    public static function getSiteVar($var, $section=null) {
        return $GLOBALS['siteConfig']->getVar($var, $section);
    }

    public static function getOptionalSiteVar($var, $default='', $section=null) {
        return $GLOBALS['siteConfig']->getOptionalVar($var, $default, $section);
    }

    public static function getSiteSection($section) {
        return $GLOBALS['siteConfig']->getSection($section);
    }

    public static function getOptionalSiteSection($section) {
        return $GLOBALS['siteConfig']->getOptionalSection($section);
    }

    /**
      * Returns a string from the site configuration (strings.ini)
      * @param string $var the key to retrieve
      * @param string $default an optional default value if the key is not present
      * @return string the value of the string or the default 
      */
    public static function getSiteString($var) {
        static $config;
        if (!$config) {
            $config = ConfigFile::factory('strings', 'site');
        }
        
        return $config->getVar($var);
    }
    
    public static function getOptionalSiteString($var, $default='') {
        static $config;
        if (!$config) {
            $config = ConfigFile::factory('strings', 'site');
        }
        
        return $config->getOptionalVar($var, $default);
    }
}



