<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('News');
class NewsAPIModule extends APIModule {

    protected $id = 'news';
    protected $vmin = 1;
    protected $vmax = 2;
    protected static $defaultModel = 'NewsDataModel';
    
    protected function initializeForCommand() {
        $feeds = $this->loadFeedData();
        $responseVersion = $this->requestedVersion == 2 ? 2 : 1;
        switch($this->command) {
            case 'stories':
                $categoryID = $this->getArg('categoryID');
                $start = $this->getArg('start');
                $limit = $this->getArg('limit');
                $mode = $this->getArg('mode');

                $feed = $this->getFeed($categoryID);
                $feed->setStart($start);
                $feed->setLimit($limit);
                $items = $feed->items($start, $limit);

                $totalItems = $feed->getTotalItems();

                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->formatStory($story, $mode);
                }
                $response = array(
                    'stories' => $stories,
                    'moreStories' => ($totalItems - $start - $limit),
                );
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);
                break;

            case 'categories':
                $response = array();
                foreach ($feeds as $index => $feedData) {
                    $response[] = array('id' => strval($index),
                    					'title' => strip_tags($feedData['TITLE']),
                                        'show_images'=>isset($feedData['SHOW_IMAGES']) ? (bool) $feedData['SHOW_IMAGES'] : true,
                                        'show_pubdate'=>isset($feedData['SHOW_PUBDATE']) ? (bool) $feedData['SHOW_PUBDATE'] : false,
                                        'show_author' => isset($feedData['SHOW_AUTHOR']) ? (bool) $feedData['SHOW_AUTHOR'] : false,
                                        'show_link' => isset($feedData['SHOW_LINK']) ? (bool) $feedData['SHOW_LINK'] : false,
                                        'show_body_thumbnail' => isset($feedData['SHOW_BODY_THUMBNAIL']) ? (bool) $feedData['SHOW_BODY_THUMBNAIL'] : true,
                                        'show_body_image' => isset($feedData['SHOW_BODY_IMAGE']) ? (bool) $feedData['SHOW_BODY_IMAGE'] : true,
                    					);
                }
                $this->setResponse($response);
                $this->setResponseVersion($responseVersion);

                break;

            case 'search':
                $categoryID = $this->getArg('categoryID');
                $searchTerms = $this->getArg('q');
                $feed = $this->getFeed($categoryID);
	            $this->setLogData($searchTerms);
                $items = $feed->search($searchTerms);

                $stories = array();
                foreach ($items as $story) {
                    $stories[] = $this->formatStory($story, 'full');
                }
                $this->setResponse($stories);
                $this->setResponseVersion($responseVersion);
                break;

            default:
                 $this->invalidCommand();
                 break;
        }
    }
    
    protected function formatStory($story, $mode) {
       $item = array(
            'GUID'        => $story->getGUID(),
            'link'        => $story->getLink(),
            'title'       => Sanitizer::htmlStripTags2UTF8($story->getTitle()),
            'description' => Sanitizer::htmlStripTags2UTF8($story->getDescription()), 
            'pubDate'     => $story->getPubTimestamp()
       );

       if($story->getContent()) {
           if($mode == 'full') {
                $item['body'] = $story->getContent();
           }
           $item['hasBody'] = TRUE;
       } else {
           $item['hasBody'] = FALSE;
       }

       $thumbnail = $story->getThumbnail();
       if($thumbnail && $thumbnail->getURL()) {
         $key = $this->requestedVersion < 2 ? 'image' : 'thumbnail';
         $item[$key] = array(
           'src'    => $thumbnail->getURL(),
           'width'  => $thumbnail->getWidth(),
           'height' => $thumbnail->getHeight()
         );
       }
       
       if ($this->requestedVersion >= 2) {
           $image = $story->getImage();
           if($image && $image->getURL()) {
               $item['image'] = array(
                    'src'    => $image->getURL(),
                    'width'  => $image->getWidth(),
                    'height' => $image->getHeight()
               );
           }
        }
       
       $author = $story->getAuthor();
       $item['author'] = $author ? $author : "";
       return $item;
    }

    // copied from NewsWebModule.php
  public function getFeed($index) {
      $feeds = $this->loadFeedData();
      if (isset($feeds[$index])) {
        
        $feedData = $feeds[$index];
        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
        $controller = NewsDataModel::factory($modelClass, $feedData);

        return $controller;
    } else {
        throw new KurogoConfigurationException($this->getLocalizedString('ERROR_INVALID_FEED', $index));
    }
  }

}
