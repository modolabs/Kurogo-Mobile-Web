<?php

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . '/mobi-config/mobi_lib_constants.php';

define("FEED_STATE_NULL", 0);
define("FEED_STATE_FRESH", 1);

class NewsOffice {

  private $cache; // check cache for XML
  private $type; // type parameter for XML query (used to specify dev feed)

  public function __construct() {
    $this->cache = True;
    $this->type = NULL;
  }

  public function disable_cache() {
    $this->cache = False;
  }

  public function use_development_feed() {
    $this->type = 'dev';
  }

  private static $channels = Array(
    0 => 'Top News',
    99 => 'Campus',
    1 => 'Engineering',
    2 => 'Science',
    3 => 'Management',
    5 => 'Architecture',
    6 => 'Humanities',
  );

  public static function story_link($story_id) {
    return NEWSOFFICE_STORY_URL . $story_id;
  }

  public function channels() {
    return self::$channels;
  }

  private static function cache_filename($channel_id, $type) {
    if($type) {
      $type = "-$type";
    } else {
      $type = "";
    }
    return dirname(__FILE__) . '/cache/NEWS_OFFICE/' . $channel_id . $type . '.xml';
  }

  public function get_news_story($channel_id, $story_id) {
    $xmldoc = $this->get_news_full_xml($channel_id);
    foreach($xmldoc->documentElement->getElementsByTagname('item') as $item) {
      if($item->getElementsByTagname('story_id')->item(0)->nodeValue == $story_id)
        return self::xml_node2item($item);
    }
  } 

  private static function empty_news_xml($version, $encoding) {
    $newdoc = new DOMDocument($version, $encoding);
    
    /* rss wrapper section */

    $root = $newdoc->createElement('rss');
    $newdoc->appendChild($root);

    $version = $newdoc->createAttribute('version');
    $version->appendChild($newdoc->createTextNode('2.0'));
    $root->appendChild($version);

    $channel = $newdoc->createElement('channel');
    $root = $root->appendChild($channel);

    $title = $newdoc->createElement('title');
    $channel->appendChild($title);

    $link = $newdoc->createElement('link');
    $channel->appendChild($link);
    $link->appendChild($newdoc->createTextNode('http://web.mit.edu/newsoffice'));

    $description = $newdoc->createElement('description');
    $channel->appendChild($description);

    return $newdoc;
  }

  private function get_news_full_xml($channel_id) {
    $cache_file = self::cache_filename($channel_id, $this->type);

    $news_url = NEWSOFFICE_FEED_URL;

    $query = Array();
    if($this->type) {
      $query['type'] = $this->type;
    }

    if ($channel_id) {
      $query['category'] = $channel_id;
    }

    $xmldoc = new DOMDocument();

    $is_fresh = FALSE;

    if($this->cache) {
      $loaded = self::load_from_cache($xmldoc, $channel_id, $this->type);
    } else {
      $loaded = FEED_STATE_NULL;
    }

    $query['lastModified'] = $loaded;

    switch ($loaded) {
      case FEED_STATE_FRESH:   
      break;

      case FEED_STATE_NULL:
        unset($query['lastModified']);
      default:
        // the HTTP extension is not currently installed in our build of PHP
        // so we just suppress warnings for 304 codes
        $body = @file_get_contents(NEWSOFFICE_FEED_URL . '?' . http_build_query($query));

        if (!in_array('HTTP/1.1 304 Not Modified', $http_response_header)) {
	  if ($xmldoc->loadXML($body)) {
	    $cache_file = self::cache_filename($channel_id, $this->type);
	    $fh = fopen($cache_file, 'w');
	    fwrite($fh, $body);
	    fclose($fh);
	  } else {
	    self::load_from_cache($xmldoc, $channel_id, $this->type);
	  }
	}

        break;
    }

    return $xmldoc;
  }

  public function get_last_story_id($channel_id) {
    $xmldoc = $this->get_news_full_xml($channel_id);
    $items = $xmldoc->documentElement->getElementsByTagName('item');
    $last_item = $items->item($items->length-1);
    return $last_item->getElementsByTagname('story_id')->item(0)->nodeValue;
  }

  public function get_first_story_id($channel_id) {
    $xmldoc = $this->get_news_full_xml($channel_id);
    $items = $xmldoc->documentElement->getElementsByTagName('item');
    $first_item = $items->item(0);
    return $first_item->getElementsByTagname('story_id')->item(0)->nodeValue;
  }

  public function get_news_xml($channel_id, $story_id, $ascending=TRUE) {
    $xmldoc = $this->get_news_full_xml($channel_id);
    
    // now we filter by story id

    /* from here on the XML can be assumed to be fresh */

    if ($story_id !== NULL) {
      $stories_retrieved = -1;
    } else {
      $stories_retrieved = 0;
    }

    $newdoc = self::empty_news_xml($xmldoc->xmlVersion, $xmldoc->encoding);
    $channel = $newdoc->documentElement->getElementsByTagname('channel')->item(0);

    // retrieve the first 10 stories after/(or before) story_id is encountered
    // for now assume the feed is sorted in exactly the desired display order
    // not even checking "featured" tag
    $items_node_list = $xmldoc->documentElement->getElementsByTagname('item');
    for($i = 0; $i < $items_node_list->length; $i++) {
      // implement the foreach loop manually (in order to do reverse order looping)
      $item_index = $ascending ? $i : $items_node_list->length - $i - 1;
      $item = $items_node_list->item($item_index);
      
      if ($stories_retrieved >= 0) {
	if ($stories_retrieved == 10)
	  break;
	else {
	  $item = $newdoc->importNode($item, TRUE);
	  $channel->appendChild($item);
	  $stories_retrieved++;
	}
      } else {
	if ($item->getElementsByTagname('story_id')->item(0)->nodeValue == $story_id) {
	  $stories_retrieved = 0; }
      }
    }

    return $newdoc;
  }

  function get_search_results($search_terms, $start, $limit) {
    $query = array(
      'searchword' => $search_terms,
      'ordering' => 'newest',
      'start' => $start,
      'limit' => $limit
    );
    
    $body = @file_get_contents(NEWSOFFICE_SEARCH_URL . '&' . http_build_query($query));
    $xmldoc = new DOMDocument("1.0", "utf-8");
    $xmldoc->loadXML($body);
    return array(
      'totalResults' => $xmldoc->documentElement->getAttribute('totalResults'),
      'items' => $this->xml2array($xmldoc),
    );
  }

  /*
   * @param channel_id id corresponding to category of stories
   * @param story_id return stories either before or after this id
   * @param after_or_before if true return stories after id otherwise stories before id
   */
  function get_news($channel_id, $story_id, $after_or_before) {
    $xml = $this->get_news_xml($channel_id, $story_id, $after_or_before);
    $stories = $this->xml2array($xml);
    if(!$after_or_before) {
      $stories = array_reverse($stories);
    }
    return $stories;
  }

  private function xml2array($xml) {
    $items = array();
    foreach($xml->getElementsByTagname('item') as $item_xml) {
      $items[] = self::xml_node2item($item_xml);
    }

    return $items;
  }

  private function xml_node2item($xml_node) {
      $images = array();
      foreach($xml_node->getElementsByTagname('otherImages') as $image_xml) {
        $images[] = NewsImage::xml2news_image($image_xml);
      }

      $item = array(
        'images' => $images,
	'body' => getNodeValue($xml_node,'body'),
	'link' => getNodeValue($xml_node,'link'),
	'category' => getNodeValue($xml_node,'category'),
	'story_id' => getNodeValue($xml_node,'story_id'),
	'featured' => intval(getNodeValue($xml_node,'featured')) != 0,
	'author' => getNodeValue($xml_node,'author'),
	'title' => getNodeValue($xml_node,'title'),
	'description' => getNodeValue($xml_node,'description'),
	'unixtime' => strtotime(getNodeValue($xml_node,'postDate')),
      );
      if($xml_node->getElementsByTagname('image')->length) {
        $main_image = NewsImage::xml2news_image($xml_node->getElementsByTagname('image')->item(0));
        if($main_image->thumb_url) {
          $item['main_image'] = $main_image;
        }
      }
      return $item;
  }

  function load_from_cache($xmldoc, $channel_id, $type) {
    $cache_file = self::cache_filename($channel_id, $type);
    if (file_exists($cache_file)) {
      $xml = file_get_contents($cache_file);
      if ($xmldoc->loadXML($xml)) {
	if (time() - filemtime($cache_file) < 300) { // cache 5 mins before pinging news office
	  return FEED_STATE_FRESH;

	} else {
	  $lastModified = (int) $xmldoc->documentElement->attributes->getNamedItem('lastModified')->nodeValue;
	  return $lastModified;
	}
      }
    }
    return FEED_STATE_NULL; // no cache or failed to load XML
  }
}

function getNodeValue($xml, $tagname) {
  return $xml->getElementsByTagname($tagname)->item(0)->nodeValue;
}

class NewsImage {

  var $thumb_url;
  var $small_url;
  var $small_dimensions;

  var $full_url;
  var $full_dimensions;

  var $credits;
  var $caption;
  
  public static function xml2news_image($image_xml) {
    $image = new self();

    if($image_xml->getElementsByTagname('thumbURL')->length > 0) {
      $image->thumb_url = $image_xml->getElementsByTagname('thumbURL')->item(0)->nodeValue;
    }

    if($image_xml->getElementsByTagname('smallURL')->length > 0) {
      $small_url_xml = $image_xml->getElementsByTagname('smallURL')->item(0);
      $image->small_url = $small_url_xml->nodeValue;
      $image->small_dimensions = array(
        'width' => $small_url_xml->getAttribute('width'),
        'height' => $small_url_xml->getAttribute('height'),
      );
    }

    if($image_xml->getElementsByTagname('fullURL')->length > 0) {
      $full_url_xml = $image_xml->getElementsByTagname('fullURL')->item(0);
      $image->full_url= $full_url_xml->nodeValue;
      $image->full_dimensions = array(
	'width'=> $full_url_xml->getAttribute('width'),
	'height' => $full_url_xml->getAttribute('height'),
      );
    }
      
    $image->credits = getNodeValue($image_xml,'imageCredits');
    $image->caption = getNodeValue($image_xml,'imageCaption');

    return $image;
  }
}

?>
