<?php

require WEBROOT . "/mobile-about/WhatsNew.inc";

class Home {
  public static $whats_new;
  public static $time;
  public static $whats_new_count;

  public static function init() {
    self::$whats_new = new WhatsNew();
    self::$time = WhatsNew::getLastTime();
    self::$whats_new_count = self::$whats_new->count(self::$time);
   
  }
}

Home::init();

?>
