<?php
require_once LIB_DIR.'/feeds/RSS.php';

class WhatsNew extends RSS {
  protected $rss_url = WHATS_NEW_PATH;

  protected $custom_tags = array('body', 'shortName');
  protected $custom_html_tags = array('<a><b><br><del><em><i><ins><strong>', '');

  private static $topitem_timeout = 14;
  private static $newuser_timeout =  30;
  private static $cookie_timeout = 160;

  public function get_items() {
    $feed = array_reverse($this->get_feed_html());
    return $feed;
  }

  public function count($time) {
    $count = 0;
    foreach($this->get_feed() as $item) {
      if($item["unixtime"] >= $time) {
        $count++;
      }
    }
    return $count;
  }

  public static function getLastTime() {
    if(isset($_COOKIE["whatsnewtime"])) {
      return intval($_COOKIE["whatsnewtime"]);
    } else {
      // no time set go back one month
      return time() - self::$newuser_timeout*24*60*60;
    }
  }

  public static function setLastTime() {
    // expires 160 days from now
    setcookie("whatsnewtime", time(), time() + self::$cookie_timeout*24*60*60, "/");
  }

  public function getTopItemName() {
    foreach($this->get_items() as $item) {
      if(time() - $item["unixtime"] < self::$topitem_timeout*24*60*60) {
        return $item["shortName"];
      } else {
        return NULL;
      }
    }
  }
}

?>
