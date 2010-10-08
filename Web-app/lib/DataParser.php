<?php

abstract class DataParser
{
    abstract public function parseData($data);
    protected $encoding='utf-8';
    protected $debugMode=false;

    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }

    protected function setEncoding($encoding)
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
    
    public function __construct()
    {
        $this->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
    }
    
}

