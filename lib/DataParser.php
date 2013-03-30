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
 * @package ExternalData
 */

/**
 * A generic class to handle the parsing of external data
 * @package ExternalData
 */
includePackage('DataParser');
abstract class DataParser
{
    abstract public function parseData($data);
    
    const PARSE_MODE_STRING=1;
    const PARSE_MODE_FILE=2;
    const PARSE_MODE_RESPONSE=3;
    protected $encoding='utf-8';
    protected $parseMode=self::PARSE_MODE_RESPONSE;
    protected $initArgs=array();
    protected $debugMode=false;
    protected $totalItems = null;
    protected $haltOnParseErrors = true;
    protected $options = array();
    protected $response;

    public function setOption($option, $value) {
        $this->options[$option] = $value;
    }

    public function getOption($option) {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }
    
    public function setResponse(DataResponse $response) {
        $this->response = $response;
    }
        
    public function parseResponse(DataResponse $response) {
        $this->setResponse($response);
        return $this->parseData($this->response->getResponse());
    }
    
    protected function getResponseRetriever() {
        if ($this->response) {
            return $this->response->getRetriever();
        }
    }
    
    public function getParseMode() {
        return $this->parseMode;
    }

    public function getTotalItems() {
        return $this->totalItems;
    }

    protected function setTotalItems($total) {
        $this->totalItems = $total;
        if ($this->response) {
            $this->response->setContext('totalItems', $total);
        }
    }
    
    public static function factory($parserClass, $args)
    {
        Kurogo::log(LOG_DEBUG, "Initializing DataParser $parserClass", "data");

        if (isset($args['PACKAGE'])) {
            Kurogo::includePackage($args['PACKAGE']);
        }

        if (!class_exists($parserClass)) {
            throw new KurogoConfigurationException("Parser class $parserClass not defined");
        } 
        
        $parser = new $parserClass;
        
        if (!$parser instanceOf DataParser) {
            throw new KurogoConfigurationException("$parserClass is not a subclass of DataParser");
        }
        
        $parser->init($args);
        return $parser;
    }

    public function haltOnParseErrors($bool) {
        $this->haltOnParseErrors = (bool) $bool;
    }
    
    public function init($args) {
        $this->initArgs = $args;
        if (isset($args['HALT_ON_PARSE_ERRORS'])) {
            $this->haltOnParseErrors($args['HALT_ON_PARSE_ERRORS']);
        }
        
        $this->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));

        $cacheClass = isset($args['CACHE_CLASS']) ? $args['CACHE_CLASS'] : 'DataCache';
        $this->cache = DataCache::factory($cacheClass, $args);
    }

    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode ? true : false;
    }

    public function setEncoding($encoding) {
        $this->encoding = $encoding;
    }
    
    public function getEncoding() {
        return $this->encoding;
    }

    public function parseFile($filename) {
        return $this->parseData(file_get_contents($filename));
    }

    public function clearInternalCache() {
        $this->setTotalItems(null);
    }
    
}
