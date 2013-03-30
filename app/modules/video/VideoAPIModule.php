<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Video');

class VideoAPIModule extends APIModule {    
    protected $id='video';  // this affects which .ini is loaded
    protected $vmin = 1;
    protected $vmax = 2;
    protected $feeds = array();
    protected static $defaultModel = 'VideoDataModel';

    protected function arrayFromVideo($video, $section) {
        $videoArray = array(
            "id"              => $video->getID(),
            "type"            => $video->getType(),
            "title"           => $video->getTitle(),
            "description"     => Sanitizer::htmlStripTags2UTF8($video->getDescription()),
            "author"          => $video->getAuthor(),
            "published"       => array(
                'date'          => $video->getPublished()->format('Y-m-d H:i:s'),
                'timezone_type' => 1, // PHP 5.3 internal type -- deprecated
                'timezone'      => $video->getPublished()->format('P'),
            ),
            "date"            => $video->getPublished()->format('M n, Y'),
            "url"             => $video->getURL(),
            "image"           => $video->getImage(),
            "width"           => $video->getWidth(),
            "height"          => $video->getHeight(),
            "aspect"          => $video->getAspectRatio(),
            "duration"        => $video->getDuration(),
            "tags"            => $video->getTags(),
            "mobileURL"       => $video->getMobileURL(),
            "streamingURL"    => $video->getStreamingURL(),
            "stillFrameImage" => $video->getStillFrameImage(),
            "playerURL"       => FULL_URL_BASE . $this->configModule . '/player?' . http_build_query(array('section'=>$section,'videoid'=>$video->getID())),
            );
        
        if ($this->requestedVersion >= 2) {
            $videoArray['published']['timestamp'] = $video->getPublished()->format('U');
            $videoArray['playerURL'] = $video->getPlayerURL();
        }
        
        return $videoArray;
    }

    protected function getFeed($feed=null) {
        $feed = isset($this->feeds[$feed]) ? $feed : $this->getDefaultSection();
        $feedData = $this->feeds[$feed];

        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
        $controller = VideoDataModel::factory($modelClass, $feedData);

        return $controller;
    }

    protected function getDefaultSection() {
        return key($this->feeds);
    }

    public function initializeForCommand() {
        $this->feeds = $this->loadFeedData();

        switch ($this->command) {
        case 'sections':
            $this->setResponse(VideoModuleUtils::getSectionsFromFeeds($this->feeds));
            $this->setResponseVersion($this->requestedVersion);
            break;
        case 'videos':
        case 'search':            
            // videos commands requires one argument: section.
            // search requires two arguments: section and q (query).
            $section = $this->getArg(array('feed', 'section'));

            $controller = $this->getFeed($section);
            $videos = array();

            // TODO: this isn't the right place to hard code paging limits

            if ($this->command == 'search') {
                $limit = 20;
                $query = $this->getArg(array('filter','q'));                
	            $this->setLogData($query);
                $controller->setLimit($limit);
                $items = $controller->search($query);
            }
            else {
                $limit = 50;
                $controller->setLimit($limit);
                $items = $controller->items();
            }

            foreach ($items as $video) {
                $videos[] = $this->arrayFromVideo($video, $section);
            }

            $this->setResponse($videos);
            $this->setResponseVersion($this->requestedVersion);
            break; 
                
        case 'detail':
            $section = $this->getArg(array('feed', 'section'));
            $controller = $this->getFeed($section);
            $videoid = $this->getArg(array('id', 'videoid'));

            if ($video = $controller->getItem($videoid)) {
                $result = $this->arrayFromVideo($video, $section);
                $this->setResponse($result);
                $this->setResponseVersion($this->requestedVersion);
            } else {
                $this->throwError(new KurogoError(1, "Video Not Found", "Video not found"));
            }
                        
            break;
        default:
            $this->invalidCommand();
            break;
        }
    }
}
