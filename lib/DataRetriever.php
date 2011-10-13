<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the retrieval of external data
 * @package ExternalData
 */
abstract class DataRetriever {

    abstract public function retrieveData();
    abstract public function getCacheKey();
    
    protected $dataController;
    protected $response;
    
    public function setDataController(ExternalDataController $dataController) {
        $this->dataController = $dataController;
    }
    
    public function getDataController() {
        return $this->dataController;
    }
    
    public static function factory($retrieverClass, $args) {
        Kurogo::log(LOG_DEBUG, "Initializing DataRetriever $retrieverClass", "data");
        if (!class_exists($retrieverClass)) {
            throw new KurogoConfigurationException("Parser class $retrieverClass not defined");
        } 
        
        $retriever = new $retrieverClass;
        
        if (!$retriever instanceOf DataRetriever) {
            throw new KurogoConfigurationException("$retriever is not a subclass of DataRetriever");
        }
        
        $retriever->init($args);
        return $retriever;
    }
    
    public function getResponse() {
        return $this->response;
    }
    
    public function getResponseHeaders() {
        return $this->response->getHeaders();
    }

    public function getResponseStatus() {
        return $this->response->getStatus();
    }

    public function getResponseCode() {
        return $this->response->getCode();
    }

    public function getResponseHeader($header) {
        return $this->response->getHeader($header);
    }
}
