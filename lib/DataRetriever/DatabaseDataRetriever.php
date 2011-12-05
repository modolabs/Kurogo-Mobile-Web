<?php

class DatabaseDataRetriever extends DataRetriever
{
    protected $connection;
    protected $sql;
    protected $parameters=array();
    
    protected function init($args) {
        parent::init($args);
        
        if (!isset($args['DB_TYPE'])) {
            $args = array_merge(Kurogo::getSiteSection('database'), $args);
        }
        
        if (isset($args['SQL'])) {
            $this->setSQL($args['SQL']);
        }

        if (isset($args['PARAMETERS']) && is_array($args['PARAMETERS'])) {
            $this->setParameters($args['PARAMETERS']);
        }
        
        $this->connection = new db($args);                
    }

    public function setSQL($sql) {
        $this->sql = $sql;
    }
    
    public function sql() {
        return $this->sql;
    }

    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    public function parameters() {
        return $this->parameters;
    }
    
    public function setQuery($array) {
        list($sql, $parameters) = $array;
        $this->setSQL($sql);
        $this->setParameters($parameters);
    }
    
    protected function initRequest() {
    }

    protected function retrieveResponse() {

        $this->initRequest();
        $response = $this->initResponse();

        if ($sql = $this->sql()) {
            $result = $this->connection->query($sql, $this->parameters());
            $response->setResponse($result);
        }        

        return $response;
    }
}