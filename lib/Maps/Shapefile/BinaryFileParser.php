<?php

abstract class BinaryFileParser extends DataParser
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

    public function setFilename($filename) {
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

    public function parseData($data) {
        $this->setup();
        $this->readHeader();
        $this->readBody();
        $this->cleanup();
    }

    public function setup() {
        $this->position = 0;
        if (!$this->handle = fopen($this->filename, 'rb')) {
            throw new Exception("unable to open file {$this->filename}");
        }
    }

    public function cleanup() {
        fclose($this->handle);
    }
}
