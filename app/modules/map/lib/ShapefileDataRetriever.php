<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class ShapefileDataRetriever extends DataRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'ShapefileDataParser';
    protected $fileStem;

    public function init($args) {
        parent::init($args);
        $this->fileStem = $args['BASE_URL'];
    }

    protected function retrieveResponse() {
        $response = $this->initResponse();
        
        if (strpos($this->fileStem, '.zip') !== false) {

            if (!class_exists('ZipArchive')) {
                throw new KurogoException("class ZipArchive (php-zip) not available");
            }
            $response->setContext('zipped', true);

            $zip = new ZipArchive();
            if (strpos($this->fileStem, 'http') === 0 || strpos($this->fileStem, 'ftp') === 0) {
                $tmpFile = Kurogo::tempFile();
                copy($this->fileStem, $tmpFile);
                $zip->open($tmpFile);
            } else {
                $zip->open($this->fileStem);
            }
            // locate valid shapefile components
            $shapeNames = array();
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('/(.+)\.(shp|dbf|prj)$/', $zip->getNameIndex($i), $matches)) {
                    $shapeName = $matches[1];
                    $extension = $matches[2];
                    if (!isset($shapeNames[$shapeName])) {
                        $shapeNames[$shapeName] = array();
                    }
                    $shapeNames[$shapeName][] = $extension;
                }
            }

            $responseData = array();
            foreach ($shapeNames as $shapeName => $extensions) {
                if (in_array('dbf', $extensions) && in_array('shp', $extensions)) {
                    $fileData = array(
                        'dbf' => $zip->getFromName("$shapeName.dbf"),
                        'shp' => $zip->getFromName("$shapeName.shp"));

                    if (in_array('prj', $extensions)) {
                        $prjData = $zip->getFromName("$shapeName.prj");
                        $fileData['projection'] = new MapProjection($prjData);
                    }

                    $responseData[$shapeName] = $fileData;
                }
            }
            $response->setResponse($responseData);

        } elseif (realpath_exists("{$this->fileStem}.shp") && realpath_exists("{$this->fileStem}.dbf")) {
            $response->setContext('zipped', false);
            $response->setContext('shp', "{$this->fileStem}.shp");
            $response->setContext('dbf', "{$this->fileStem}.dbf");
            if (realpath_exists("{$this->fileStem}.prj")) {
                $prjData = file_get_contents("{$this->fileStem}.prj");
                $response->setContext('projection', new MapProjection($prjData));
            }

        } else {
            throw new KurogoDataException("Cannot find {$this->fileStem}");
        }

        return $response;
    }














}
