<?php

define('KUROGO_VERSION', '1.0');

class Kurogo
{
    public static function getLanguages() {
        return array(
            'en'=>'English'
        );
    }

    public static function getLifetimeOptions() {
        return array(
            ""    =>'Default',
            0     =>'None',
            10    =>'10 seconds',
            30    =>'30 seconds',
            60    =>'1 minute',
            120   =>'2 minutes',
            300   =>'5 minutes',
            600   =>'10 minutes',
            900   =>'15 minutes',
            1800  =>'30 minutes',
            3600  =>'1 hour',
            7200  =>'2 hours',
            10800 =>'3 hours',
            21600 =>'6 hours',
            43200 =>'12 hours',
            86400 =>'1 day',
            604800 =>'1 week',
            1209600=>'2 weeks',
            2419200=>'4 weeks',
            15552000=>'180 days',
            31536000=>'1 year'
        );
    }
    
    public static function getHashAlgos() {
        return array_combine(hash_algos(), hash_algos());
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

    public static function getSiteAccessControlListArrays() {
        $acls = array();
        foreach (self::getSiteAccessControlLists() as $acl) {
            $acls[] = $acl->toArray();
        }
        return $acls;
    }

    public static function getSiteAccessControlLists() {
        $config = ConfigFile::factory('acls', 'site', ConfigFile::OPTION_CREATE_EMPTY);
        $acls = array();
        
        foreach ($config->getSectionVars() as $aclArray) {
            if ($acl = AccessControlList::createFromArray($aclArray)) {
                $acls[] = $acl;
            }
        }
        
        return $acls;
    }
    
    public static function checkCurrentVersion() {
        $url = "http://modolabs.com/kurogo/checkversion.php?" . http_build_query(array(
            'version'=>KUROGO_VERSION,
            'base'=>FULL_URL_BASE,
            'site'=>SITE_KEY,
            
        ));
        return trim(file_get_contents($url));
    }
}



