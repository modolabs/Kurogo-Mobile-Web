<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class APIResponse
{
    public $id='';
    public $tag;
    public $command;
    public $version;
    public $error;
    public $warnings;
    public $response;
    public $context;
    public $contexts=array();
    
    public function __construct($id=null, $tag=null, $command=null, $context=null) {
        if (isset($id)) {
            $this->id = $id;
        }

        if (isset($tag)) {
            $this->tag = $tag;
        }
        
        if (isset($command)) {
            $this->command = $command;
        }

        if (isset($context)) {
            $this->context = $context;
        }
        
        $this->response = new stdClass();
    }
    
    public function getVersion() {
        return $this->version;
    }

    public function setVersion($version) {
        $this->version = intval($version);
    }

    public function setContext($context) {
        $this->context = $context;
    }
    
    public function setError(KurogoError $error) {
        $this->error = $error;
    }
    
    public function addWarning(KurogoWarning $warning) {
        if (!isset($this->warnings)) {
            $this->warnings = array();
        }
        $this->warnings[] = $warning;
    }
    
    public function setResponse($response) {
        $this->response = $response;
    }
    
    public function getJSONOutput() {
        $contexts = Kurogo::sharedInstance()->getActiveContexts();
        $this->contexts = array_keys($contexts);
        if (is_null($this->version)) {
            throw new KurogoException('APIResponse version must be set before display');
        }
    
        return json_encode($this);
    }

    public function display() {
        $json = $this->getJSONOutput();
        $size = strlen($json);
        header("Content-Type: application/json; charset=" . Kurogo::getCharset());
        header("Content-Length: " . $size);
        echo $json;
        return $json;
    }
}
