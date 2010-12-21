<?php

abstract class DataParser
{
    abstract public function parseData($data);
    protected $encoding='utf-8';
    protected $debugMode=false;
    
    public static function factory($args)
    {
        $parserClass = isset($args['PARSER_CLASS']) ? $args['PARSER_CLASS'] : __CLASS__;
        
        if (!class_exists($parserClass)) {
            throw new Exception("Parser class $parserClass not defined");
        } 
        
        $parser = new $parserClass;
        $parser->init($args);
        return $parser;
    }
    
    public function init($args)
    {
        $this->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
    }

    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }
    
    public function getEncoding()
    {
        return $this->encoding;
    }

    public function parseFile($filename) 
    {
        return $this->parseData(file_get_contents($filename));
    }
    
}

