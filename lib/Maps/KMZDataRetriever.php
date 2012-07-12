<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/* this class is the same as URLRetriever except
 * the file gets unzipped once cached on the filesystem
*/
class KMZDataRetriever extends URLDataRetriever
{
    protected function retrieveResponse()
    {
        if (!class_exists('ZipArchive')) {
            throw new KurogoException("class ZipArchive (php-zip) not available");
        }

        $tmpFile = Kurogo::tempFile();

        // this is the same as parent
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
                
        $this->requestParameters = $this->parameters();
        // the following are private functions in URLDataRetriever
        //$this->requestMethod = $this->setContextMethod();
        //$this->requestHeaders = $this->setContextHeaders();
        //$this->requestData = $this->setContextData();
        
        Kurogo::log(LOG_INFO, "Retrieving $this->requestURL", 'url_retriever');

        // the creation of $data is different from parent
        copy($this->requestURL, $tmpFile);
        $zip = new ZipArchive();
        $zip->open($tmpFile);
        $data = $zip->getFromIndex(0);
        unlink($tmpFile);

        // this is the same as parent
        $http_response_header = isset($http_response_header) ? $http_response_header : array();

        $response = $this->initResponse();
        $response->setRequest($this->requestMethod, $this->requestURL, $this->requestParameters, $this->requestHeaders, null);

        $response->setResponse($data);
        $response->setResponseHeaders($http_response_header);
        
        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $response->getCode(), strlen($data)), 'url_retriever');
        
        return $response;
    }
}
