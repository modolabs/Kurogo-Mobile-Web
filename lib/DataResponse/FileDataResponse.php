<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
