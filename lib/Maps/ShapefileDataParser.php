<?php

class ShapefileGeometry extends BasePlacemark implements MapGeometry
{
    protected $index;
    protected $geometry;
    protected $bbox;
    protected $properties;
    protected $category;

    // parent requires a geometry parameter
    // we don't because we are geometry
    public function __construct() { }

    // TODO: these are placeholder implementations of
    // getTitle and getSubtitle.  the title and subtitle
    // fields should be config values.  guessing fields
    // happens to work great for this boston feed but it
    // will not in general.

    public function getTitle() {
        if (count($this->properties) > 1) {
            $array = array_values($this->properties);
            return next($array);
        }
        return null;
    }

    public function getSubtitle() {
        if (count($this->properties) > 2) {
            $array = array_values($this->properties);
            next($array);
            return next($array);
        }
        return null;
    }

    public function setFields($properties) {
        $this->properties = $properties;
    }

    public function setIndex($index) {
        $this->index = $index;
    }

    public function getIndex() {
        return $this->index;
    }

    public function readGeometry($geometry) {
        $this->geometry = $geometry;
    }

    public function getGeometry() {
        return $this;
    }

    public function setBBox($bbox) {
        $this->bbox = $bbox;
    }

    public function getCenterCoordinate() {
        if (isset($this->bbox)) {
            $point = array(
                'lat' => ($this->bbox['ymin'] + $this->bbox['ymax']) / 2,
                'lon' => ($this->bbox['xmin'] + $this->bbox['xmax']) / 2,
                );
            return $point;
        }

        return null;
    }
}

class ShapefilePoint extends ShapefileGeometry
{
    public function getCenterCoordinate() {
        return $this->geometry;
    }
}

class ShapefileMultiPoint extends ShapefileGeometry
{
}

class ShapefilePolyline extends ShapefileGeometry
{
}

class ShapefilePolygon extends ShapefileGeometry
{
}

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

abstract class BinaryFileParser
{
    protected $filename;
    protected $wordSize = 4;
    protected $fileSize;
    protected $position = 0;
    protected $bigEndian = true;

    protected $handle;
    protected $readBuffer = array();
    protected $readCache = array();

    abstract public function readHeader();
    abstract public function readBody();
    abstract public function readRecord();

    public function __construct($filename) {
        $this->filename = $filename;
    }

    protected function read($length) {
        $readLength = $length - count($this->readBuffer);

        $chars = array();
        if ($readLength > 0) {
            $chars = array_merge(
                $this->readBuffer,
                str_split(fread($this->handle, $readLength))
                );

            $this->readBuffer = array();

        } else {
            for ($i = 0; $i < $length; $i++) {
                $chars[] = array_unshift($this->readBuffer);
            }
        }
        if (count($chars) != $length) {
            throw new Exception("not able to read $length characters");
        }

        $this->position += $length;
        $this->readCache = array_merge($chars, $this->readBuffer);

        return $chars;
    }

    protected function undoRead() {
        $this->position -= count($this->readCache);
        $this->readBuffer = $this->readCache;
        $this->readCache = array();
    }

    protected function readChar() {
        $chars = $this->read(1);
        return $chars[0];
    }

    protected function readString($length) {
        return implode('', $this->read($length));
    }

    protected function readWord($length=null, $reverseEndian=false) {
        $bigEndian = $reverseEndian ? !$this->bigEndian : $this->bigEndian;
        if ($length === null) {
            $length = $this->wordSize;
        }
        $chars = $this->read($length);
        switch ($length) {
            case 4:
                if ($bigEndian) {
                    return (ord($chars[0]) << 24) + (ord($chars[1]) << 16)
                         + (ord($chars[2]) << 8) + ord($chars[3]);
                } else {
                    return (ord($chars[3]) << 24) + (ord($chars[2]) << 16)
                         + (ord($chars[1]) << 8) + ord($chars[0]);
                }
            case 2:
                if ($bigEndian) {
                    return (ord($chars[0]) << 8) + ord($chars[1]);
                } else {
                    return (ord($chars[1]) << 8) + ord($chars[0]);
                }
            case 1:
                return ord($chars[0]);
            default:
                $value = 0;
                if ($bigEndian) {
                    for ($i = 0; $i < $length; $i++) {
                        $value = ($value << 8) + ord($chars[$i]);
                    }
                } else {
                    for ($i = $length - 1; $i >= 0; $i--) {
                        $value = ($value << 8) + ord($chars[$i]);
                    }
                }
                return $value;
        }
    }

    protected function readDouble($reverseEndian=false) {
        $word = $this->readWord(8, $reverseEndian);
        return ieee64FromLong($word);
    }

    protected function skip($length) {
        $this->readCache = array();
        $this->readBuffer = array();
        fread($this->handle, $length);
        $this->position += $length;
    }

    protected function skipTo($position) {
        $this->skip($position - $this->position);
    }

    public function parseData() {
        $this->setup();
        $this->readHeader();
        $this->readBody();
        $this->cleanup();
    }

    public function setup() {
        $this->position = 0;
        $this->handle = fopen($this->filename, 'rb');
    }

    public function cleanup() {
        fclose($this->handle);
    }
}

class ShapefileDataParser extends BinaryFileParser implements MapDataParser
{
    private $features = array();
    private $dbfParser;
    private $bbox; // global bbox for file
    private $category;

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

    public function getListItems()
    {
        return $this->features;
    }

    public function getAllFeatures()
    {
        return $this->features;
    }

    public function getChildCategories()
    {
        return array();
    }

    public function getProjection() {
        // TODO move WKTParser from ShapeFileDataController to here
        return null;
    }

    public function getParsedData() {
        return $this->features;
    }

    public function setDBFParser(DBase3FileParser $parser) {
        $this->dbfParser = $parser;
        $this->dbfParser->setup();
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
        $feature->setIndex($recordNumber);
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


class DBase3FileParser extends BinaryFileParser
{
    protected $wordSize = 1;

    private $dbaseVersionId;
    private $lastModified;
    private $numRecords;
    private $fields;
    private $records;

    private static $readTypeMap = array(
        'C' => 'readTrimString', // < 254 bytes
        'N' => 'readTrimString', // string repr of number, < 18 bytes
        //'L' => 1 byte logical
        //'D' => date, YYYYMMDD
        //'M' => memo, 10 byte pointer
        'F' => 'readTrimString', // 20 bytes
        //'B' => binary
        //'G' => ole
        //'P' => picture
        //'Y' => currency
        //'T' => datetime
        'I' => 'readWord', // 4 byte little endian
        //'V' => verified
        //'X' => variant
        //'@' => timestamp, 8 bytes
        'O' => 'readDouble', // 8 bytes
        '+' => 'readWord', // autoincrement value, 4 byte long
        );

    /**************** header contents ******************************
     * Byte     Length  Type  Endian  Description
     * 0        1       int           version number
     * 1        3       int           date of last update (YY, MM, DD)
     * 4        4       long  Little  number of records 
     * 8        2       int   Little  number of bytes in header
     * 10       2       int   Little  number of bytes in record
     * 12       2       int   Big     reserved, zero-filled
     * 14       1       int   Big     transaction flag 0x0 ended 0x1 started
     * 15       1       int   Big     encryption flag 0x0 no 0x1 yes
     * 16       4       int   Big     reserved
     * 20       8       int   Big     reserved
     * 28       1       int   Big     MDX flag
     * 29       1       int   Big     language driver ID
     * 30       2       int   Big     reserved, zero-filled
     * 32       32d     int   Big     field descriptor array
     * 32+32d   1       char          0x0D (Field Descriptor Terminator)
     ********************************************************************/
    public function readHeader() {
        $this->dbaseVersionId = $this->readWord();

        $year = $this->readWord() + 1900;
        $month = $this->readWord();
        $day = $this->readWord();
        $this->lastModified = mktime(0, 0, 0, $year, $month, $day);

        $this->numRecords = $this->readWord(4, true);
        $this->headerLength = $this->readWord(2, true);
        $this->recordLength = $this->readWord(2, true);

        $this->skipTo(32);

        while ($this->readChar() != "\x0d") {
            $this->undoRead();
            $this->readFieldHeader();
        }
    }

    private function readTrimString($length) {
        $string = $this->readString($length);
        return trim($string, "\0 \r\n");
    }

    /**************** field descriptor contents *******************
     * Byte  Length  Type  Description
     * 0     11      char  field name (zero-filled)
     * 11    1       char  field type [BCDNLM@I+F0G]
     * 12    4       int   field data address (in memory for dbase)
     * 16    1       int   field length (max 255)
     * 17    1       int   decimal count (max 15)
     * 18    2       int   reserved
     * 20    1       int   work area ID
     * 21    2       int   reserved
     * 23    1       int   SET FIELDS flag
     * 24    7       int   reserved
     * 31    2       int   index field flag 0x0 no key 0x1 key exists
     ****************************************************************/
    private function readFieldHeader() {
        $field = array(
            'name' => trim($this->readString(11), "\x00"),
            'type' => $this->readChar(),
            'address' => $this->readWord(4),
            'length' => $this->readWord(),
            'decimals' => $this->readWord(),
            );
        $this->skip(14);
        $this->fields[] = $field;
    }

    public function readRecord() {
        $deleted = $this->readWord();
        $record = array();
        foreach ($this->fields as $field) {
            $readType = self::$readTypeMap[$field['type']];
            $record[$field['name']] = $this->$readType($field['length']);
        }
        return $record;
    }

    public function readBody() {
        for ($i = 0; $i < $this->numRecords; $i++) {
            $this->records[] = $this->readRecord();
        }
        if ($this->readChar() != "\x1a") {
            throw new Exception("error finding dBase file terminator");
        }
    }

}



