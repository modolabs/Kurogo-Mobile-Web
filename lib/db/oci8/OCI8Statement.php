<?php

class OCI8Statement implements KurogoDatabaseResponse {

    protected $statement;

    public function __construct($statement){
        $this->statement = $statement;
    }

    public function fetch(){
        return oci_fetch_assoc($this->statement);
    }
}
