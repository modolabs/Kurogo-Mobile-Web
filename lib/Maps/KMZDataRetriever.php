<?php

/* this class is the same as URLRetriever except
 * the file gets unzipped once cached on the filesystem
*/
class KMZDataRetriever extends URLDataRetriever
{
    /**
     * Retrieves the data using the config url. The default implementation uses the file_get_content()
     * function to retrieve the request. Subclasses would need to implement this if a simple GET request
     * is not sufficient (i.e. you need POST or custom headers). 
     * @return HTTPDataResponse a DataResponse object
     */
    protected function retrieveData()
    {
        if (!class_exists('ZipArchive')) {
            throw new KurogoException("class ZipArchive (php-zip) not available");
        }

        $tmpDir = Kurogo::tempDirectory();
        if (!is_writable($tmpDir)) {
            throw new KurogoConfigurationException("Temporary directory $tmpDir not available");
        }
        $tmpFile = $tmpDir.'/tmp.kmz';

        // this is the same as parent
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }
                
        $this->requestParameters = $this->parameters();
        $this->requestMethod = $this->setContextMethod();
        $this->requestHeaders = $this->setContextHeaders();
        $this->requestData = $this->setContextData();
        
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
        $response->setRequest($this->requestMethod, $this->requestURL, $this->requestParameters, $this->requestHeaders);

        $response->setResponse($data);
        $response->setResponseHeaders($http_response_header);
        
        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $response->getCode(), strlen($data)), 'url_retriever');
        
        return $response;
    }


}
