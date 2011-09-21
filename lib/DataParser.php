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
    const PARSE_MODE_STRING=1;
    const PARSE_MODE_FILE=2;
    protected $encoding='utf-8';
    protected $parseMode=self::PARSE_MODE_STRING;
    protected $debugMode=false;
    protected $totalItems = null;
    protected $haltOnParseErrors = true;
    protected $dataController;
    
    public function getParseMode() {
        return $this->parseMode;
    }

    public function getTotalItems() {
        return $this->totalItems;
    }

    public function setDataController(DataController $dataController) {
        $this->dataController = $dataController;
    }

    protected function setTotalItems($total) {
        $this->totalItems = $total;
    }
    
    public static function factory($parserClass, $args)
    {
        Kurogo::log(LOG_DEBUG, "Initializing DataParser $parserClass", "data");
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
        if (isset($args['HALT_ON_PARSE_ERRORS'])) {
            $this->haltOnParseErrors($args['HALT_ON_PARSE_ERRORS']);
        }
        
        $this->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
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
