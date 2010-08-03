<?php

require_once "rss_services.php";
require_once "DiskCache.inc";

define('IMAGE_CACHE_EXTENSION', '/api/newsimages');

class GazetteRSS extends RSS {

  private static $diskCache;
  private static $searchCache;
  private static $feeds = NULL;
  private static $imageWriter;

  // when we resize images, store width/height in
  // a state variable
  private static $lastWidth;
  private static $lastHeight;

  // TODO: move this somewhere else instead of keeping in code
  private static $channels = array(
    array('title' => 'All News', 
          'url' => 'http://feeds.feedburner.com/HarvardGazetteOnline'),
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

      // news articles get updated continuously, so make the timeout short
      self::$diskCache = new DiskCache(CACHE_DIR . '/GAZETTE', 300, TRUE);
      self::$diskCache->setSuffix('.xml');
      self::$diskCache->preserveFormat();

      // allow cached search results to stick around longer
      self::$searchCache = new DiskCache(CACHE_DIR . '/GAZETTE_SEARCH', 3600, TRUE);
      self::$searchCache->setSuffix('.xml');
      self::$searchCache->preserveFormat();

      self::$imageWriter = new DiskCache(WEBROOT . IMAGE_CACHE_EXTENSION, PHP_INT_MAX, TRUE);
    }
  }

  public static function getChannels() {
    $result = array();
    foreach (self::$channels as $channel) {
      $result[] = $channel['title'];
    }
    return $result;
  }

  public static function searchArticles($searchTerms, $lastStoryId=NULL) {

    // we will just store filenames by search terms
    if (!self::$searchCache->isFresh($searchTerms)) {
      $query = http_build_query(array('s' => $searchTerms, 'feed' => 'rss2'));
      $url = NEWS_SEARCH_URL . '?' . $query;
      $contents = file_get_contents($url);
      self::$searchCache->write($contents, $searchTerms);
    }

    $cacheFile = self::$searchCache->getFullPath($searchTerms);
    return self::loadArticlesFromCache($cacheFile, $lastStoryId);
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

      $result = self::loadArticlesFromCache($cacheFile, $lastStoryId);
      self::$feeds[$cacheId] = $result;
      return $result;
    }
  }

  private static function loadArticlesFromCache($path, $lastStoryId=NULL) {

    $doc = new DOMDocument();
    $doc->load($path);

    $newdoc = new DOMDocument($doc->xmlVersion, $doc->encoding);
    $rssRoot = $newdoc->importNode($doc->documentElement);
    $newdoc->appendChild($rssRoot);

    $channelRoot = $newdoc->createElement('channel');
    $rssRoot->appendChild($channelRoot);

    if ($lastStoryId === NULL) {
      // provide a flag to the native app so it knows how many stories
      // are in this feed, since we only return up to 10
      $numItems = $doc->getElementsByTagName('item')->length;
      self::appendDOMAttribute($newdoc, $channelRoot, 'items', $numItems);
    }

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
        // download and resize thumbnail image
        $thumb = $item->getElementsByTagName('image')->item(0);
        $thumbUrl = $item->getElementsByTagName('url')->item(0);
        $newThumbUrlString = self::imageUrl(self::cacheImage($thumbUrl->nodeValue, 76));

        // replace url in rss feed
        $newThumbUrl = $newdoc->createElement('url', $newThumbUrlString);
        $thumb->replaceChild($newThumbUrl, $thumbUrl);

        // remove images from main <content> tag

        $contentNode = $item->getElementsByTagName('encoded')->item(0);
        $content = $contentNode->nodeValue;
        $contentHTML = new DOMDocument();
        $contentHTML->loadHTML($content);

        //$otherImages = $newdoc->createElement('otherImages');
        //$numImages = 0;

        foreach ($contentHTML->getElementsByTagName('img') as $imgTag) {
          // skip 1px tracking images
          if ($imgTag->getAttribute('width') == '1') {
            $imgTag->parentNode->removeChild($imgTag);
            continue;
          }

          // 300px for inline images
          $src = $imgTag->getAttribute('src');
          $cachedImageFile = self::cacheImage($src, 300);
          if ($cachedImageFile) {
            $newSrcUrlString = self::imageUrl($cachedImageFile);
            $otherImage = $newdoc->createElement('image');

            //$fullUrl = $contentHTML->createElement('fullURL', $newSrcUrlString);
          
            $fullUrl = $contentHTML->createElement('img');

            self::appendDOMAttribute($contentHTML, $fullUrl, 'src', $newSrcUrlString);
            self::appendDOMAttribute($contentHTML, $fullUrl, 'width', self::$lastWidth);
            self::appendDOMAttribute($contentHTML, $fullUrl, 'height', self::$lastHeight);
            /*
            // TODO: make a function to add an attribute to a DOMNode
            $fullUrlSrc = $contentHTML->createAttribute('src');
            $fullUrlSrcText = $contentHTML->createTextNode($newSrcUrlString);
            $fullUrlSrc->appendChild($fullUrlSrcText);
            $fullUrl->appendChild($fullUrlSrc);

            $fullUrlWidth = $contentHTML->createAttribute('width');
            $fullUrlWidthText = $contentHTML->createTextNode(self::$lastWidth);
            $fullUrlWidth->appendChild($fullUrlWidthText);
            $fullUrl->appendChild($fullUrlWidth);

            $fullUrlHeight = $contentHTML->createAttribute('height');
            $fullUrlHeightText = $contentHTML->createTextNode(self::$lastHeight);
            $fullUrlHeight->appendChild($fullUrlHeightText);
            $fullUrl->appendChild($fullUrlHeight);
            */
            //$otherImage->appendChild($fullUrl);
            //$node = $newdoc->importNode($otherImage, TRUE);
            //$otherImages->appendChild($node);

            $imgTag->parentNode->replaceChild($fullUrl, $imgTag);
            //$numImages++;

          } else {
            $imgTag->parentNode->removeChild($imgTag);
          }

        } // foreach

        //if ($numImages) {
        //  $item->appendChild($otherImages);
        //}

        $cdata = $newdoc->createCDATASection($contentHTML->saveHTML());
        $contentNode->replaceChild($cdata, $contentNode->firstChild);

        $channelRoot->appendChild($item);
        $count++;
      } // else
    } // foeach

    $result = $newdoc->saveXML();

    return $result;
  }

  private static function cacheName($url) {
    return end(explode('/', $url));
  }

  /** image caching and manipulation **/

  private static function cacheImage($imgUrl, $newWidth=NULL, $newHeight=NULL) {

    $imageName = self::imageName($imgUrl, $newWidth, $newHeight);
    if (self::$imageWriter->isFresh($imageName)) {
      return $imageName;
    } else {
      $imageStr = file_get_contents($imgUrl);
      $bytes = strlen($imageStr);

      // if the image is too large, php will run out of memory
      // i haven't found the threshold so we will set a limit that
      // includes most images from the gazette feed.
      if ($bytes > 500000) {
        return FALSE;
      }

      // do this temporarily to keep track of how long it takes us
      // to create resized images
      error_log("$imgUrl is $bytes bytes", 0);

      if ($imageStr) {
         $image = imagecreatefromstring($imageStr);
         if ($image) {
   
           if ($newWidth === NULL && $newHeight === NULL) {
             if (self::$imageWriter->writeImage($image, $imageName)) {
               // save state
               self::$lastWidth = $newWidth;
               self::$lastHeight = $newHeight;

               return $imageName;
             } else {
               return FALSE;
             }
           }

           $oldOriginX = 0;
           $oldOriginY = 0;
     
           $oldWidth = imagesx($image);
           $oldHeight = imagesy($image);
           // if both newWidth and newHeight are specified,
           // decide whether we need to truncate in one dimension
           if ($newWidth !== NULL && $newHeight !== NULL) {
             $xScale = $maxWidth / $oldWidth;
             $yScale = $maxHeight / $oldHeight;
     
             // we might not get round numbers above, so use percent difference
             if (abs($xScale / $yScale - 1) > 0.05) {
               if ($yScale < $xScale) { // truncate height from center
                 $oldHeightIfSameRatio = $maxHeight * $oldWidth / $maxWidth;
                 $oldOriginY = ($oldHeight - $oldHeightIfSameRatio) / 2;
                 $oldHeight = $oldHeightIfSameRatio;
               } else { // truncate width from center
                 $oldWidthIfSameRatio = $maxWidth * $oldHeight / $maxHeight;
                 $oldOriginX = ($oldWidth - $oldWidthIfSameRatio) / 2;
                 $oldWidth = $oldWidthIfSameRatio;
               }
             }
           }

           // if only one of maxWidth or maxHeight is specified,
           // populate the other based on original image ratio
           elseif ($newWidth !== NULL && $newHeight === NULL) {     
             $newHeight = $oldHeight * $newWidth / $oldWidth;
           }

           elseif ($newWidth === NULL && $newHeight !== NULL) {
             $newWidth = $oldWidth * $newHeight / $oldHeight;
           }

           $newImage = imagecreatetruecolor($newWidth, $newHeight);
           imagecopyresized($newImage, $image, 0, 0, $oldOriginX, $oldOriginY, $newWidth, $newHeight, $oldWidth, $oldHeight);


           if (self::$imageWriter->writeImage($newImage, $imageName)) {
             // save state
             self::$lastWidth = $newWidth;
             self::$lastHeight = $newHeight;

             return $imageName;
           }

        } // if $image
      } // if $imageStr
    } // else
  }

  private static function imageName($imgUrl, $width=NULL, $height=NULL) {
    $extension = substr($imgUrl, -4);
    $hash = crc32($imgUrl);

    return sprintf("%u", $hash) 
      . ($width === NULL ? '' : '_' . $width)
      . ($height === NULL ? '' : 'x' . $height)
      . $extension;
  }

  private static function imageUrl($filename) {
    $port = $_SERVER['SERVER_PORT'] == 80 
      ? '' 
      : ':' . $_SERVER['SERVER_PORT'];

    return 'http://' . $_SERVER['SERVER_NAME'] . $port
      . IMAGE_CACHE_EXTENSION . '/' . $filename;
  }

  private static function appendDOMAttribute($doc, $parent, $attribName, $attribValue) {
    $attributeNode = $doc->createAttribute($attribName);
    $valueNode = $doc->createTextNode($attribValue);
    $attributeNode->appendChild($valueNode);
    $parent->appendChild($attributeNode);
  }

}

GazetteRSS::init();


