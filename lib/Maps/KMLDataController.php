<?php

class KMLDataController extends MapLayerDataController
{
    protected $parserClass = 'KMLDataParser';

    protected function cacheFileSuffix()
    {
        return '.kml';
    }
    
    protected function retrieveData($url)
    {
    	if (strpos($url, 'kmz') !== false) {
    	    if (!class_exists('ZipArchive')) {
    	        die("class ZipArchive (php-zip) not supported");
    	    }
            $tmpDir = $GLOBALS['siteConfig']->getVar('TMP_DIR');
    	    $tmpFile = $tmpDir.'/tmp.kmz';

    	    copy($url, $tmpFile);
    	    $zip = new ZipArchive();
    	    $zip->open($tmpFile);
    	    $contents = $zip->getFromIndex(0);
    	    unlink($tmpFile);
    	    return $contents; // this is false on failure, same as file_get_contents
    	} else {
    	    return parent::retrieveData($url);
    	}
    }
}

