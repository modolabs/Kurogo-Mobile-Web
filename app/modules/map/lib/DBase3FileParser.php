<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

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
            throw new KurogoDataException("error finding dBase file terminator");
        }
    }

    public function cleanup() {
        $this->dbaseVersionId = null;
        $this->lastModified = null;
        $this->numRecords = null;
        $this->fields = null;
    }
}
