<?php

class FileDataResponse extends DataResponse
{
    protected $responseFile;   
 
    public function getResponseFile() {
        return $this->responseFile;
    }

    public function setResponseFile($url) {
        $this->responseFile = $url;
    }

    public function setRequest($url) {
        $this->responseFile = $url;
        if (!is_readable($url)) {
            if (!file_exists($url)) {
                $this->responseError = "File not found";
            } else {
                $this->responseError = "Unable to load file";
            }
        }
    }
   
}