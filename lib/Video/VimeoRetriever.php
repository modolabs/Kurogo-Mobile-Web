<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

 class VimeoRetriever extends URLDataRetriever implements ItemDataRetriever
 {
    protected $DEFAULT_PARSER_CLASS='VimeoDataParser';
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['CHANNEL']) && strlen($args['CHANNEL'])) {
            $this->setOption('channel', $args['CHANNEL']);
        }
    }
    
    public function setOption($option, $value) {
        parent::setOption($option, $value);
        
        switch ($option)
        {
            case 'channel':
                $this->setBaseUrl('http://vimeo.com/api/v2/channel/' . $value . '/videos.json');
                break;
            case 'author':
                $this->setBaseUrl('http://vimeo.com/api/v2/' . $value . '/videos.json');
                break;
        }
    }
    
    protected function isValidID($id) {
        return preg_match("/^[0-9]+$/", $id);
    }
    
	 // retrieves video based on its id
	public function getItem($id, &$response=null) {
	    if (!$this->isValidID($id)) {
	        return false;
	    }

        $url = 'http://vimeo.com/api/v2/video/' . $id . '.json';
        $this->setBaseURL($url);
        if ($items = $this->getData($response)) {
            return isset($items[0]) ? $items[0] : false;
        }
        
        return false;
	}
}
 
class VimeoDataParser extends DataParser
{
    protected function parseEntry($entry) {
        $video = new VimeoVideoObject();
        $video->setURL($entry['url']);
        if (isset($entry['mobile_url'])) {
            $video->setMobileURL($entry['mobile_url']);
        }
        $video->setTitle($entry['title']);
        $video->setDescription($entry['description']);
        $video->setDuration($entry['duration']);
        $video->setID($entry['id']);
        $video->setImage($entry['thumbnail_small']);
        $video->setStillFrameImage($entry['thumbnail_large']);
        
        if (isset($entry['tags'])) {
            $video->setTags($entry['tags']);
        }
        $video->setWidth(Kurogo::arrayVal($entry, 'width'));
        $video->setHeight(Kurogo::arrayVal($entry, 'height'));
        
        $video->setAuthor($entry['user_name']);
        $published = new DateTime($entry['upload_date']);
        $video->setPublished($published);
        return $video;
    }
    
    public function parseData($data) {
        if ($data = json_decode($data, true)) {
            
            $videos = array();
            if (is_array($data)) {
                if (isset($data['id'])) {
                } else {
                    $this->setTotalItems(count($data));
                    foreach ($data as $entry) {
                        $videos[] = $this->parseEntry($entry);
                    }
                    
                    return $videos;
                }                
            }
        } 

        return array();
        
    }
}

class VimeoVideoObject extends VideoObject
{
    protected $type = 'vimeo';

    public function canPlay(DeviceClassifier $deviceClassifier) {
        if (in_array($deviceClassifier->getPlatform(), array('blackberry','bbplus'))) {
            return false;
        }

        return true;
    }
    
    public function getPlayerURL() {
        return sprintf("http://player.vimeo.com/video/%s", $this->getID());
    }
}
