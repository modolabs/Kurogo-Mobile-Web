<?php

includePackage('Maps', 'Shapefile');

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
    private $mapProjection;

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

        $filename = $args['BASE_URL'];

        $this->setFilename($filename . '.shp');
        $this->dbfParser = new DBase3FileParser();
        $this->dbfParser->setFilename($filename . '.dbf');
        $this->dbfParser->setup();

        $prjFile = $filename . '.prj';
        if (file_exists($prjFile)) {
            $prjData = file_get_contents($prjFile);
            $this->mapProjection = new MapProjection($prjData, 'wkt');
        }
    }

    public function getListItems()
    {
        return $this->features;
    }

    public function getAllPlacemarks()
    {
        return $this->features;
    }

    public function getChildCategories()
    {
        return array();
    }

    public function getProjection() {
        return $this->mapProjection;
    }

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
            throw new Exception('header already read');
        }
        if ($this->readWord(null, true) != 9994) {
        	throw new Exception('incorrect header for .shp file');
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
        }
        $feature->setId($recordNumber);
        $feature->setFields($this->dbfParser->readRecord());
        //$feature->setCategory($this->category);

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

    private function addPoint() {
        $point = new ShapefilePoint();
        $point->readGeometry($this->readPoint());
        return $point;
    }

    private function addMultiPoint() {
        $bbox = $this->readBBox();
        $numPoints = $this->readWord();
        $points = array();
        for ($i = 0; $i < $numPoints; $i++) {
            $points[] = $this->readPoint();
        }
        $point = new ShapefileMultiPoint();
        $point->readGeometry($points);
        $point->setBBox($bbox);
        return $point;
    }

    private function addPolyline() {
        $bbox = $this->readBBox();
        $numParts = $this->readWord();
        $numPoints = $this->readWord();

        $paths = array();
        $lastPathEnd = 0;
        for ($i = 0; $i < $numParts; $i++) {
            $points = array();
            // indexes of when points belong to next path
            $pathEnd = $this->readWord();
            for ($j = $lastPathEnd; $j < $pathEnd; $j++) {
                $points[] = $this->readPoint();
            }
            $paths[] = $points;
            $lastPathEnd = $pathEnd;
        }

        $polyline = new ShapefilePolyline();
        $polyline->readGeometry($paths);
        $polyline->setBBox($bbox);
        return $polyline;
    }

    private function addPolygon() {
        $bbox = $this->readBBox();
        $numRings = $this->readWord();
        $numPoints = $this->readWord();

        $rings = array();
        $lastRingEnd = 0;
        for ($i = 0; $i < $numRings; $i++) {
            $points = array();
            // indexes of when points belong to next path
            $ringEnd = $this->readWord();
            for ($j = $lastRingEnd; $j < $ringEnd; $j++) {
                $points[] = $this->readPoint();
            }
            $rings[] = $points;
            $lastPathEnd = $pathEnd;
        }

        $polygon = new ShapefilePolygon();
        $polygon->readGeometry($polygon);
        $polygon->setBBox($bbox);
        return $polygon;
    }

    public function readBody() {
        while ($this->position < $this->fileSize) {
            $this->features[] = $this->readRecord();
        }
    }
}






