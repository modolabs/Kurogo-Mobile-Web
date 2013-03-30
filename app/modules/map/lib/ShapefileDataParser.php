<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

function ieee64FromLong($arg) {
    $mantissa = 1.0;
    $sign = 1;
    if ($arg & 0x8000000000000000) {
        $sign = -1;
    }
    $exponent = (($arg & 0x7ff0000000000000) >> 52) - (pow(2, 11 - 1) - 1);
    $mantissaBits = $arg & 0x000fffffffffffff;
    for ($pos = 0; $pos < 52; $pos++) {
        $bit = $mantissaBits & 1;
        $mantissa += pow(2, $pos - 52) * $bit;
        $mantissaBits = $mantissaBits >> 1;
    }
    return $sign * pow(2, $exponent) * $mantissa;
}

class ShapefileDataParser extends BinaryFileParser implements MapDataParser
{
    private $features = array();
    private $dbfParser;
    private $bbox; // global bbox for file
    private $category;
    private $projection;
    private $mapProjector;
    private $titleField = null;
    private $subtitleField = null;
    private $feedId;

    protected $bigEndian = false;

    public static $shapeTypes = array(
        '0' => 'addNull',
        '1' => 'addPoint',
        '3' => 'addPolyline',
        '5' => 'addPolygon',
        '8' => 'addMultiPoint',
        //'11' => 'addPointZ',
        //'13' => 'addPolylineZ',
        //'15' => 'addPolygonZ',
        //'18' => 'addMultiPointZ',
        //'21' => 'addPointM',
        //'23' => 'addPolylineM',
        //'25' => 'addPolygonM',
        //'28' => 'addMultiPointM',
        //'31' => 'addMultiPatch',
    );

    public function init($args) {
        parent::init($args);

        if (isset($args['TITLE_FIELD'])) {
            $this->titleField = $args['TITLE_FIELD'];
        }

        if (isset($args['SUBTITLE_FIELD'])) {
            $this->subtitleField = $args['SUBTITLE_FIELD'];
        }

        $this->feedId = mapIdForFeedData($args);
    }

    public function parseResponse(DataResponse $response) {
        // this parser doesn't depend on any internal state, so never re-parse
        if ($this->features) {
            return $this->features;
        }

        $this->dbfParser = new DBase3FileParser();
        $this->mapProjector = new MapProjector();

        if ($response->getContext('zipped')) {
            $content = $response->getResponse();
            foreach ($content as $filename => $fileData) {
                $this->setContents($fileData['shp']);
                $this->dbfParser->setContents($fileData['dbf']);
                $this->dbfParser->setup();
                $this->projection = isset($fileData['projection']) ? $fileData['projection'] : null;
                $this->mapProjector->setSrcProj($this->projection);
                $this->doParse();
            }

        } else {
            $this->projection = $response->getContext('projection');
            $this->mapProjector->setSrcProj($this->projection);
            $this->setFilename($response->getContext('shp'));
            $this->dbfParser->setFilename($response->getContext('dbf'));
            $this->dbfParser->setup();
            $this->doParse();
        }
        return $this->features;
    }

    public function getListItems()
    {
        return $this->features;
    }

    public function placemarks()
    {
        return $this->features;
    }

    public function categories()
    {
        return array();
    }

    // MapDataParser

    public function getProjection() {
        return $this->mapProjection;
    }

    public function getId() {
        return $this->feedId;
    }

    ///

    public function parseData($data) {
        parent::parseData($data);
        return $this->features;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    /************** main (.shp) file header description ************
     * Byte  Field         Value        Type     Order
     * 0     File Code     9994         Integer  Big
     * 4     Unused        0            Integer  Big
     * 8     Unused        0            Integer  Big
     * 12    Unused        0            Integer  Big
     * 16    Unused        0            Integer  Big
     * 20    Unused        0            Integer  Big
     * 24    File Length   File Length  Integer  Big
     * 28    Version       1000         Integer  Little
     * 32    Shape Type    Shape Type   Integer  Little
     * 36    Bounding Box  Xmin         Double   Little
     * 44    Bounding Box  Ymin         Double   Little
     * 52    Bounding Box  Xmax         Double   Little
     * 60    Bounding Box  Ymax         Double   Little
     * 68*   Bounding Box  Zmin         Double   Little
     * 76*   Bounding Box  Zmax         Double   Little
     * 84*   Bounding Box  Mmin         Double   Little
     * 92*   Bounding Box  Mmax         Double   Little
     ***************************************************************/
    public function readHeader() {
        if ($this->position > 0) {
            throw new KurogoDataException('header already read');
        }
        if ($this->readWord(null, true) != 9994) {
        	throw new KurogoDataException('incorrect header for .shp file');
        }
        $this->skipTo(24);
        $this->fileSize = $this->readWord(null, true) * 2;
        $this->skipTo(32);
        $this->bbox = $this->readBBox();
        $this->skipTo(100);
        $this->dbfParser->readHeader();
    }

    /************** main (.shp) file record headers ************
     * Byte  Field           Value           Type     Order
     * 0     Record Number   Record Number   Integer  Big
     * 4     Content Length  Content Length  Integer  Big
     ***************************************************************/
    public function readRecord() {
        $recordNumber = $this->readWord(null, true);
        $recordLength = $this->readWord(null, true);
        $shapeType = $this->readWord();

        if (isset(self::$shapeTypes[$shapeType])) {
            $readFunction = self::$shapeTypes[$shapeType];
            $feature = $this->$readFunction();
        } else {
            throw new KurogoDataException("geometry $shapeType not currently supported");
        }
        $fields = $this->dbfParser->readRecord();
        if ($this->titleField) {
            if (!strlen($fields[$this->titleField])) {
                return null;
            }
            $feature->setTitleField($this->titleField);
        }
        if ($this->subtitleField) {
            $feature->setSubtitleField($this->subtitleField);
        }
        $feature->setId($recordNumber);
        $feature->setFields($fields);
        return $feature;
    }

    private function readPoint() {
        return array(
            'lon' => $this->readDouble(),
            'lat' => $this->readDouble(),
            );
    }

    private function readBBox() {
        return array(
            'xmin' => $this->readDouble(),
            'ymin' => $this->readDouble(),
            'xmax' => $this->readDouble(),
            'ymax' => $this->readDouble(),
            );
    }

    private function readPolyStructure() {
        $numParts = $this->readWord();
        $numPoints = $this->readWord();

        $paths = array();
        $pathStarts = array();
        for ($i = 0; $i < $numParts; $i++) {
            $pathStarts[] = $this->readWord();
        }

        $start = $pathStarts[0];
        for ($i = 1; $i < $numParts; $i++) {
            $points = array();
            $nextPartStart = $pathStarts[$i];
            for ($j = $start; $j < $nextPartStart; $j++) {
                $points[] = $this->readPoint();
            }
            $paths[] = $points;
            $start = $nextPartStart;
        }
        for ($j = $start; $j < $numPoints; $j++) {
                $points[] = $this->readPoint();
        }
        $paths[] = $points;

        return $paths;
    }

    private function addPoint() {
        $struct = $this->readPoint();
        if ($this->projection) { // if projection is null, projector will do nothing
            $struct = $this->mapProjector->projectPoint($struct);
        }
        $geometry = new MapBasePoint($struct);
        $point = new ShapefilePlacemark($geometry);
        return $point;
    }

    private function addMultiPoint() {
        $bbox = $this->readBBox();
        $numPoints = $this->readWord();
        $points = array();
        for ($i = 0; $i < $numPoints; $i++) {
            $points[] = $this->readPoint();
        }
        // TODO: complete this
    }

    private function addPolyline() {
        $bbox = $this->readBBox();
        $struct = $this->readPolyStructure();
        $centroid = array(
            'lat' => ($bbox['ymin'] + $bbox['ymax']) / 2,
            'lon' => ($bbox['xmin'] + $bbox['xmax']) / 2,
            );
        if ($this->projection) {
            $centroid = $this->mapProjector->projectPoint($centroid);
        }
        // TODO: find out if there are polylines that have more than one element
        // in the outermost array. and if so, if they should be connected.
        $geometry = new MapBasePolyline($struct[0], $centroid);
        if ($this->projection) {
            $geometry = $this->mapProjector->projectGeometry($geometry);
        }
        $polyline = new ShapefilePlacemark($geometry);
        return $polyline;
    }

    private function addPolygon() {
        $bbox = $this->readBBox();
        $polyStruct = $this->readPolyStructure();
        $centroid = array(
            'lat' => ($bbox['ymin'] + $bbox['ymax']) / 2,
            'lon' => ($bbox['xmin'] + $bbox['xmax']) / 2,
            );
        if ($this->projection) {
            $centroid = $this->mapProjector->projectPoint($centroid);
        }
        $geometry = new MapBasePolygon($polyStruct, $centroid);
        if ($this->projection) {
            $geometry = $this->mapProjector->projectGeometry($geometry);
        }
        $polygon = new ShapefilePlacemark($geometry);
        return $polygon;
    }

    public function readBody() {
        while ($this->position < $this->fileSize) {
            if (($feature = $this->readRecord())) {
                $this->features[] = $feature;
            }
        }
    }

    public function cleanup() {
        parent::cleanup();
        $this->dbfParser->cleanup();
    }
}






