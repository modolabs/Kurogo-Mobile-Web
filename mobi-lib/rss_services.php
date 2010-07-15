<?php

require_once "lib_constants.inc";

class Emergency extends RSS {
  protected $rss_url = EMERGENCY_RSS_URL;
  protected $custom_tags = array('version');
}
 
class ThreeDown extends RSS {
  protected $rss_url = THREEDOWN_RSS_URL;
  protected $index_by_title = True;
}

class RSS {

  protected $custom_tags = array();
  protected $custom_html_tags = array();
  protected $index_by_title = False;

  private $cache = NULL;

  // allows long living processes to disable caching
  public $use_cache = True;

  /*
   * get plain text of "description" tag in the feed
   * via get_feed_html(), but strip all html tags
   */
  public function get_feed() {
    $html_feed = $this->get_feed_html();
    if($html_feed === False) {
      return False;
    }

    $items = Array();
    foreach ($html_feed as $index => $contents) {
      $items[$index] = array(
	  "date"     => $contents['date'],
          "unixtime" => $contents['unixtime'],
	  "text"     => self::cleanText($contents['text']),
          "title"    => $contents['title'],
      );

      foreach($this->custom_tags as $custom_tag) {
	$items[$index][$custom_tag] = $contents[$custom_tag];
      }

    }

    return $items;
  }

  /*
   * get contents of "description" tag in the feed
   * leaves in selected html tags
   */
  public function get_feed_html() {
    // first check if the feed is already cached;
    if($this->cache) {
      return $this->cache;
    }

    //get the feed
    $rss_obj = new DOMDocument();

    //turn off warnings
    $rss = file_get_contents($this->rss_url);

    //if the rss feed fails to open return false
    if($rss === FALSE) {
      return FALSE;
    }

    if(!$rss_obj->loadXML($rss)) {
      return FALSE;
    }
    $rss_root = $rss_obj->documentElement;
    $items = array();

    foreach($rss_root->getElementsByTagName('item') as $counter => $item) {    
      $title = trim(self::getTag($item, 'title')->nodeValue);

      if($this->index_by_title) {
        $index = $title;
      } else {
        $index = $counter;
      }

      $items[$index] = array(
	  "date"     => date_parse(self::getTag($item, 'pubDate')->nodeValue),
          "unixtime" => strtotime(self::getTag($item, 'pubDate')->nodeValue),
	  "text"     => strip_tags(
            self::getTag($item, 'description')->nodeValue,
	    '<a><b><br><del><em><i><ins><p><strong>'), // allowed tags
          "title"    => $title
      );

      if ($this->custom_html_tags
	  && count($this->custom_tags) == count($this->custom_html_tags)) {
        $tags = array_combine($this->custom_tags, $this->custom_html_tags);
        foreach ($tags as $tag => $allowed_html_tags) {
          $custom_item = strip_tags(
            self::getTag($item, $tag)->nodeValue,
            $allowed_html_tags);
          $items[$index][$tag] = html_entity_decode($custom_item);
        }
      } else {
        foreach($this->custom_tags as $custom_tag) {
         $custom_item = self::getTag($item, $custom_tag)->nodeValue;
	 $items[$index][$custom_tag] = html_entity_decode($custom_item, ENT_QUOTES);
        }
      }
    }

    if($this->use_cache) {
      // cache the result (to prevent multiple remote calls)
      $this->cache = $items;
    }
    return $items;
  }

  private static function getTag($xml_obj, $tag) {
    $list = $xml_obj->getElementsByTagName($tag);
    if($list->length == 0) {
      throw new Exception("no elements of type $tag found");
    }
    if($list->length > 1) {
      throw new Exception("elements of type $tag not unique {$list->length} found");
    }
    return $list->item(0);
  }

  protected static function cleanText($html) {
    $html = trim(htmlspecialchars_decode($html, ENT_QUOTES));

    //replace <br>'s with line breaks
    $html = preg_replace('/<br\s*?\/?>/', "\n", $html);

    //replace <p>'s with line breaks
    $html = preg_replace('/<\/?p>/', "\n", $html);
    $html = preg_replace('/<p\s+?.*?>/', "\n", $html);
    $html = preg_replace('/\n+/',"\n", $html);

    //remove all other mark-ups
    $html = strip_tags($html);

    //replace all the non-breaking spaces
    $html = str_replace('&nbsp;', ' ', $html);
    $html = html_entity_decode($html, ENT_QUOTES);

    return trim($html);
  }

}

?>
