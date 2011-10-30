<?php

class DatabaseDataRetriever extends DataRetriever
{
    protected $connection;
    protected $sql;
    protected $parameters;
    
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

    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }
    
    public function setQuery($array) {
        list($sql, $parameters) = $array;
        $this->sql = $sql;
        $this->parameters = $parameters;
    }

    public function retrieveData() {

        $response = new DataResponse();

        if ($this->sql) {
            $result = $this->connection->query($this->sql, $this->parameters);
            $response->setResponse($result);
        }        

        return $response;
    }
}