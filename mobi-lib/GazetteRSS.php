<?php

require_once "rss_services.php";
require_once "DiskCache.inc";

class GazetteRSS extends RSS {

  private static $diskCache;
  private static $feeds = NULL;

  private static $channels = array(
    array('title' => 'All News', 
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnline'),
    array('title' => 'Harvard News',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineHarvardNews'),
    array('title' => 'In the Media',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineInTheMedia'),
    array('title' => 'Campus & Community',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineCampusCommunity'),
    array('title' => 'Arts & Culture',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineArtsCulture'),
    array('title' => 'Science & Health',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineScienceHealth'),
    array('title' => 'National & World Affairs',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineNationalWorldAffairs'),
    array('title' => 'Athletics',
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineAthletics'),
    //array('title' => 'Multimedia',
    //      'url' => 'http://feeds.feedburner.com/HarvardGazetteOnlineMultimedia'),
    );
  
  public static function init() {
    if (self::$feeds === NULL) {
      self::$feeds = array();

      self::$diskCache = new DiskCache(CACHE_DIR . '/GAZETTE', 300, TRUE);
      self::$diskCache->setSuffix('.xml');
      self::$diskCache->preserveFormat();
    }
  }

  public static function getChannels() {
    $result = array();
    foreach (self::$channels as $channel) {
      $result[] = $channel['title'];
    }
    return $result;
  }

  public static function getMoreArticles($channel=0, $lastStoryId=NULL) {
    $cacheId = ($lastStoryId === NULL) ? $channel : $channel . ':' . $lastStoryId;

    if ($channel < count(self::$channels)) {
      $channelInfo = self::$channels[$channel];
      $channelUrl = $channelInfo['url'] . '?format=xml';

      $filename = self::cacheName($channelUrl);
      if (!self::$diskCache->isFresh($filename)) {
        $contents = file_get_contents($channelUrl);
        self::$diskCache->write($contents, $filename);
      } else {
        if (array_key_exists($cacheId, self::$feeds)) {
                return self::$feeds[$cacheId];
        }
      }

      $cacheFile = self::$diskCache->getFullPath($filename);
      $doc = new DOMDocument();
      $doc->load($cacheFile);

      $newdoc = new DOMDocument($doc->xmlVersion, $doc->encoding);
      $rssRoot = $newdoc->importNode($doc->documentElement);
      $newdoc->appendChild($rssRoot);

      $channelRoot = $newdoc->createElement('channel');
      $rssRoot->appendChild($channelRoot);

      $count = 0;
      foreach ($doc->getElementsByTagName('item') as $item) {
        if ($count >= 10) {
          break;
        }

        $item = $newdoc->importNode($item, TRUE);
        if ($lastStoryId !== NULL) {
          $storyId = $item->getElementsByTagName('WPID')->item(0)->nodeValue;
	  if ($storyId == $lastStoryId) {
            $lastStoryId = NULL;
          }
        } else {
          $channelRoot->appendChild($item);
          $count++;
        }
      }

      $result = $newdoc->saveXML();
      self::$feeds[$cacheId] = $result;

      return $result;
    }

  }

  private static function cacheName($url) {
    return end(explode('/', $url));
  }

}

GazetteRSS::init();


