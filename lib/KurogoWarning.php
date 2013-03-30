<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoWarning
{
	public $code;
	public $message;
	public $file;
	public $line;
	
	public function __toString() {
	    return "Warning: {$this->message} in {$this->file} on line {$this->line}";
	}
	
	public function __construct($code, $message, $file, $line) {
        $this->setCode($code);
        $this->setMessage($message);
        $this->setFile($file);
        $this->setLine($line);
    }

    /**
     * returns warning code
     * @return int
     */
	public function getCode() {
		return $this->code;
	}

    /**
     * sets warning code
     * @param int $code
     */
 	public function setCode($code) {
		$this->code = intval($code);
	}

    /**
     * returns warning message
     * @return string
     */
	public function getMessage() {
		return $this->message;
	}

    /**
     * sets warning message
     * @param string $message
     */
	public function setMessage($message) {
		$this->message = $message;
	}

    /**
     * returns warning file name
     * @return string
     */
	public function getFile() {
		return $this->file;
	}

    /**
     * sets warning file name
     * @param string $file
     */
	public function setFile($file) {
		$this->file = $file;
	}

    /**
     * returns warning line number
     * @return string
     */
	public function getLine() {
		return $this->line;
	}

    /**
     * sets warning line number
     * @param string $line
     */
	public function setLine($line) {
		$this->line = $line;
	}
}
