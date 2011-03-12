<?php
/**
 * @package ExternalData
 */

/**
 * A generic class to handle the parsing of external data
 * @package ExternalData
 */
abstract class DataParser
{
    abstract public function parseData($data);
    protected $encoding='utf-8';
    protected $debugMode=false;
    protected $totalItems = null;
    
    public function getTotalItems() {
        return $this->totalItems;
    }

    protected function setTotalItems($total) {
        $this->totalItems = $total;
    }
    
    public static function factory($parserClass, $args)
    {
        if (!class_exists($parserClass)) {
            throw new Exception("Parser class $parserClass not defined");
        } 
        
        $parser = new $parserClass;
        
        if (!$parser instanceOf DataParser) {
            throw new Exception("$parserClass is not a subclass of DataParser");
        }
        
        $parser->init($args);
        return $parser;
    }
    
    public function init($args) {
        $this->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
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
    
}
