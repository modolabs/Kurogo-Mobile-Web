<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ZipURLDataRetriever extends URLDataRetriever
{
    protected $targetFile = null; // which file to extract from zip archive

    public function setTargetFile($filename) {
        $this->targetFile = $filename;
    }

    protected function targetFile() { // see DocxDataRetriever for override example
        return $this->targetFile;
    }

    protected function cacheKey() {
        $cacheKey = parent::cacheKey();
        $targetFile = $this->targetFile();
        if ($targetFile) {
            $cacheKey .= '.' . $targetFile;
        }
        return $cacheKey;
    }

    protected function retrieveResponse()
    {
        if (!class_exists('ZipArchive')) {
            throw new KurogoException("class ZipArchive (php-zip) not available");
        }

        $tmpFile = Kurogo::tempFile();

        // this is the same as parent
        if (!$this->requestURL = $this->url()) {
            throw new KurogoDataException("URL could not be determined");
        }

        Kurogo::log(LOG_INFO, "Retrieving $this->requestURL", 'url_retriever');

        // get data from parent request and save to temp file which we will
        // unzip and return
        $response = parent::retrieveResponse();
        file_put_contents($tmpFile, $response->getResponse());

        $zip = new ZipArchive();

        if (!$zip->open($tmpFile)) {
            throw new KurogoDataException("Could not open zip file");
        }

        $targetFile = $this->targetFile();
        if ($targetFile) {
            $index = $zip->locateName($targetFile);
        } else {
            $index = 0;
        }

        if ($index === false) { // $zip->locateName failed
            throw new KurogoDataException("Could not locate {$this->targetFile} in zip archive");
        }

        $data = $zip->getFromIndex($index);

        unlink($tmpFile);
        $zip->close();

        $response->setResponse($data);

        Kurogo::log(LOG_DEBUG, sprintf("Returned status %d and %d bytes", $response->getCode(), strlen($data)), 'url_retriever');
        
        return $response;
    }
}

