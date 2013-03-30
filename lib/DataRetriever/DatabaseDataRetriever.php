<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class DatabaseDataRetriever extends DataRetriever
{
    protected $connection;
    protected $sql;
    protected $parameters=array();
    protected $errorMsg;
    
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
        $this->initRequestIfNeeded();
        return $this->sql;
    }

    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }

    public function parameters() {
        $this->initRequestIfNeeded();
        return $this->parameters;
    }
    
    public function setQuery($array) {
        list($sql, $parameters) = $array;
        $this->setSQL($sql);
        $this->setParameters($parameters);
    }
    
    protected function retrieveResponse() {

        $this->initRequestIfNeeded();
        $response = $this->initResponse();
        $response->setResponseError($this->errorMsg);
        $response->setStartTime(microtime(true));

        if ($sql = $this->sql()) {
            $result = $this->connection->query($sql, $this->parameters());
            $response->setResponse($result);
        }        
        $response->setEndTime(microtime(true));

        return $response;
    }
}
