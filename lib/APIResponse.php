<?php

class APIResponse
{
    public $id='';
    public $command;
    public $version;
    public $error;
    public $response;
    public $context;
    
    public function __construct($id=null, $command=null, $context=null) {
        if (isset($id)) {
            $this->id = $id;
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
    
    public function setResponse($response) {
        $this->response = $response;
    }
    
    public function display() {
        if (is_null($this->version)) {
            throw new Exception('APIResponse version must be set before display');
        }
    
        echo json_encode($this);
        exit();
    }
}
