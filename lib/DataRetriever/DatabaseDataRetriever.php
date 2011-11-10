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
        
        $this->connection = new db($args);                
    }

    public function getCacheKey() {
        return false;
    }
    
    public function setSQL($sql) {
        $this->sql = $sql;
    }
    
    protected sql() {
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

    public function retrieveData() {

        $response = $this->initResponse();

        if ($sql = $this->sql()) {
            $result = $this->connection->query($sql, $this->parameters());
            $response->setResponse($result);
        }        

        return $response;
    }
}