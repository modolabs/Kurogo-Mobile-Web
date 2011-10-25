<?php

abstract class DataResponse
{
    protected $response;
    protected $responseCode;
    
    public function getResponse() {
        return $this->response;
    }

    public function getCode() {
        return $this->responseCode;
    }
}
