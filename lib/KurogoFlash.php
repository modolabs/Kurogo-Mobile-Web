<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class KurogoFlash{

    const NOTICE = 'notice';
    const ALERT = 'alert';

    private static $_instance = NULL;
    private function __clone() {}

    protected $flashes = array();
    protected $key = 'kurogoflash';
    
    private function __construct(){
        # populate flashes from session
        $this->flashes = Kurogo::arrayVal($_SESSION, $this->key);
    }

    public static function sharedInstance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }

    public static function add($key, $value){
        return KurogoFlash::sharedInstance()->addMessage($key, $value);
    }

    public function addMessage($key, $value){
        # save message to session
        $this->flashes[$key][] = $value;
        $_SESSION[$this->key][$key] = $this->flashes[$key];
    }

    public static function notice($value){
        return KurogoFlash::sharedInstance()->addMessage(KurogoFlash::NOTICE, $value);
    }

    public static function alert($value){
        return KurogoFlash::sharedInstance()->addMessage(KurogoFlash::ALERT, $value);
    }

    public function getMessages(){
        # invalidate stored flashes
        $_SESSION[$this->key] = array();
        return $this->flashes;
    }
}