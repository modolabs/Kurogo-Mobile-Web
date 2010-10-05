<?php

abstract class DataParser
{
    abstract public function parseData($data);
    protected $debugMode=false;

    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }

    public function parseFile($filename) 
    {
        return $this->parseData(file_get_contents($filename));
    }
    
}

