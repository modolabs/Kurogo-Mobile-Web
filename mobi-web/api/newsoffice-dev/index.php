<?php

$docRoot = getenv("DOCUMENT_ROOT");
require_once $docRoot . '/mobi-config/mobi_web_constants.php';
require_once WEBROOT . "api/api_header.php";

PageViews::log_api('newsoffice', 'iphone');

define("FEED_STATE_NULL", 0);
define("FEED_STATE_FRESH", 1);

$channels = Array(
  0 => 'All News',
  1 => 'Engineering',
  2 => 'Business',
  3 => 'Science',
  5 => 'Architecture and Planning',
  6 => 'Humanities, Arts, and Social Sciences',
  99 => 'Campus',
  );

$channel_id = $_REQUEST['channel'] ? $_REQUEST['channel'] : 0;
$cache_file = $cache_dir . $channel_id . '.xml';

$news_url = NEWSOFFICE_FEED_URL;

$query = Array('type' => 'dev');
if ($channel_id) {
  $query['category'] = $channel_id;
}

$xmldoc = new DOMDocument();

$is_fresh = FALSE;

$loaded = load_from_cache($xmldoc, $channel_id);
//$query['lastModified'] = $loaded;

switch ($loaded) {
 case FEED_STATE_FRESH:
   break;

 case FEED_STATE_NULL:
   unset($query['lastModified']);
 default:
   // the HTTP extension is not currently installed in our build of PHP
   // so we just suppress warnings for 304 codes
   $error_reporting = intval(ini_get('error_reporting'));
   error_reporting($error_reporting & ~E_WARNING);
     $body = file_get_contents(NEWSOFFICE_FEED_URL . '?' . http_build_query($query));
   error_reporting($error_reporting);

   if (!in_array('HTTP/1.1 304 Not Modified', $http_response_header)) {
     if ($xmldoc->loadXML($body)) {
       $cache_file = cache_filename($channel_id);
       $fh = fopen($cache_file, 'w');
       fwrite($fh, $body);
       fclose($fh);
     } else {
       load_from_cache($xmldoc, $channel_id);
     }
   }

   break;
}

/* from here on the XML can be assumed to be fresh */

if (isset($_REQUEST['story_id'])) {
  $story_id = $_REQUEST['story_id'];
  $stories_retrieved = -1;
} else {
  $stories_retrieved = 0;
}

$newdoc = new DOMDocument($xmldoc->xmlVersion, $xmldoc->encoding);

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
$title->appendChild($newdoc->createTextNode($channels[$channel_id]));

$link = $newdoc->createElement('link');
$channel->appendChild($link);
$link->appendChild($newdoc->createTextNode('http://web.mit.edu/newsoffice'));

$description = $newdoc->createElement('description');
$channel->appendChild($description);

// retrieve the first 10 stories after story_id is encountered
// for now assume the feed is sorted in exactly the desired display order
// not even checking "featured" tag
foreach ($xmldoc->documentElement->getElementsByTagName('item') as $item) {
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

echo $newdoc->saveXML();

function cache_filename($channel_id) {
  return dirname(__FILE__) . '/cache/' . $channel_id . '.xml';
}

function load_from_cache($xmldoc, $channel_id) {
  $cache_file = cache_filename($channel_id);
  if (file_exists($cache_file)) {
    $xml = file_get_contents($cache_file);
    if ($xmldoc->loadXML($xml)) {
      if (time() - filemtime($cache_file) < 300) { // cache 5 mins before pinging news office
	return FEED_STATE_FRESH;

      } else {
	return FEED_STATE_NULL;
	//$lastModified = (int) $xmldoc->documentElement->attributes->getNamedItem('lastModified')->nodeValue;
	//return $lastModified;
      }
    }
  }
  return FEED_STATE_NULL; // no cache or failed to load XML
}

?>
