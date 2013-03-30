<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

 class YouTubeRetriever extends URLDataRetriever implements SearchDataRetriever, ItemDataRetriever
 {
    protected $DEFAULT_PARSER_CLASS='YouTubeDataParser';
    protected $orderBy;
    
 	private function setStandardFilters() {
 		if ($playlist = $this->getOption('playlist')) {
			$this->setBaseUrl('https://gdata.youtube.com/feeds/api/playlists/' . $playlist);
            if(!$this->orderBy){
                $this->orderBy = 'position';
            }
 		} else {
			$this->setBaseUrl('https://gdata.youtube.com/feeds/mobile/videos');
            if(!$this->orderBy){
                $this->orderBy = 'published';
            }
		}
		
        $this->addFilter('alt', 'jsonc'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2
        $this->addFilter('orderby', $this->orderBy);
    }
    
    public function canSearch() {
 		if ($this->getOption('playlist')) {
 			return false;
 		}
    	
		return parent::canSearch();	    	
    }
    
    public function search($searchTerms, &$response=null) {
        $this->addFilter('q', $searchTerms); //set the query
        $this->addFilter('orderby', 'relevance');
        return $this->getData($response);
    }
    
    protected function init($args) {
        parent::init($args);
        if (isset($args['PLAYLIST']) && strlen($args['PLAYLIST'])) {
            $this->setOption('playlist', $args['PLAYLIST']);
        }
        if(isset($args['ORDER_BY']) && strlen($args['ORDER_BY'])){
            $this->orderBy = $args['ORDER_BY'];
        }
        $this->setStandardFilters();
    }

    protected function streamContextOpts($args) {
        $opts = parent::streamContextOpts($args);
        $opts['http']['ignore_errors'] = true;
        return $opts;
    }
    
    protected function initRequest() {
        parent::initRequest();

        if ($limit = $this->getOption('limit')) {
            $this->addFilter('max-results', $limit);
        }
        
        $start = $this->getOption('start');
        if (strlen($start)) {
            $this->addFilter('start-index', $this->getOption('start')+1);
        }
    }
    
    public function setOption($option, $value) {
        parent::setOption($option, $value);
        
        switch ($option)
        {
            case 'tag':
                $this->addFilter('category', $value);
                break;
            case 'author':
                $this->addFilter('author', $value);
                break;
        }
    }

    protected function isValidID($id) {
        return preg_match("/^[A-Za-z0-9_-]+$/", $id);
    }
    
	 // retrieves video based on its id
	public function getItem($id, &$response=null)
	{
	    if (!$this->isValidID($id)) {
	        Kurogo::log(LOG_WARNING, "Invalid YouTube id $id found", 'video');
	        return false;
	    }
        $this->setBaseUrl("https://gdata.youtube.com/feeds/mobile/videos/$id");
        $this->addFilter('alt', 'jsonc'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2

        $data = $this->getData($response);
        if (!$data) {
            $responseContent = json_decode($response->getResponse());
            if (isset($responseContent['error'])) {
                $error = $responseContent['error'];
                // TODO: be able to pass this on to the data model
                $code = $error['code'];
                $message = $error['message'];
                Kurogo::log(LOG_WARNING, "Error retrieving video ({$code}): {$message}", 'video');
            }
            return false;
        }
        
        return $data;
	}
}
 
class YouTubeDataParser extends DataParser
{
    protected function parseEntry($entry) {
    	if (isset($entry['video']['id'])) {
    		$entry = array_merge($entry, $entry['video']);
    	}
        $video = new YouTubeVideoObject();
        $video->setURL($entry['player']['default']);
        if (isset($entry['content'][6])) {
            $video->setStreamingURL($entry['content'][6]);
        }
        
        if ($aspectRatio = Kurogo::arrayVal($entry, 'aspectRatio')) {
            if ($aspectRatio == 'widescreen') {
                $video->setAspectRatio(16/9);
            }
        }
        $video->setMobileURL($entry['content']['1']);
        $video->setTitle($entry['title']);
        $video->setDescription(strip_tags($entry['description']));
        $video->setDuration($entry['duration']);
        $video->setID($entry['id']);
        
        if (IS_SECURE) {
            $video->setImage(str_replace('http://', 'https://', $entry['thumbnail']['sqDefault']));
            $video->setStillFrameImage(str_replace('http://', 'https://', $entry['thumbnail']['hqDefault']));
        } else {
            $video->setImage($entry['thumbnail']['sqDefault']);
            $video->setStillFrameImage($entry['thumbnail']['hqDefault']);
        }
        
        if (isset($entry['tags'])) {
            $video->setTags($entry['tags']);
        }
        $video->setAuthor($entry['uploader']);
        $published = new DateTime($entry['uploaded']);
        $video->setPublished($published);
        return $video;
    }
    
    public function parseData($data) {
        if ($data = json_decode($data, true)) {
            
            if (isset($data['data']['items'])) {
                $videos = array();  
                $this->setTotalItems($data['data']['totalItems']);

                foreach ($data['data']['items'] as $entry) {
                    $videos[] = $this->parseEntry($entry);
                }
                
                return $videos;
            } elseif (isset($data['data']['id'])) {
                $video = $this->parseEntry($data['data']);
                return $video;
            } else {
                return array();
            }
        } 

        return array();
        
    }
}

class YouTubeVideoObject extends VideoObject
{
    protected $type = 'youtube';
    
    public function canPlay(DeviceClassifier $deviceClassifier) {
        if (in_array($deviceClassifier->getPlatform(), array('blackberry','bbplus'))) {
            return $this->getStreamingURL();
        }

        return true;
    }
    
    public function getPlayerURL() {
        return sprintf("%s://www.youtube.com/embed/%s?showinfo=0", HTTP_PROTOCOL, $this->getID());
    }
}
