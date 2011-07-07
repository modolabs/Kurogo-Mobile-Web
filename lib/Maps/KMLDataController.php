<?php

class KMLDataController extends MapDataController
{
    protected $parserClass = 'KMLDataParser';

    protected function retrieveData($url)
    {
    	if (strpos($url, 'kmz') !== false) {
    	    if (!class_exists('ZipArchive')) {
    	        throw new Exception("class ZipArchive (php-zip) not available");
    	    }
            $tmpDir = Kurogo::tempDirectory();
            if (!is_writable($tmpDir)) {
    	        throw new Exception("Temporary directory $tmpDir not available");
            }
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

