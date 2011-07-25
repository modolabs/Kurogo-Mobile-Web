<?php

 class YouTubeVideoController extends VideoDataController
 {
    protected $DEFAULT_PARSER_CLASS='YouTubeDataParser';
    protected $playlist;
    
 	private function setStandardFilters() {
 		if ($this->playlist) {
			$this->setBaseUrl('http://gdata.youtube.com/feeds/api/playlists/' . $this->playlist);
 		} else {
			$this->setBaseUrl('http://gdata.youtube.com/feeds/mobile/videos');
		}
        $this->addFilter('alt', 'jsonc'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2
        $this->addFilter('orderby', 'published');
        if ($this->tag) {
            $this->addFilter('category', $this->tag);
        }

        if ($this->author) {
            $this->addFilter('author', $this->author);
        }
    }
    
    public function search($q, $start=0, $limit=null) {
    
        $this->setStandardFilters();
    	
        $this->addFilter('q', $q); //set the query
        $this->addFilter('max-results', $limit);
        $this->addFilter('start-index', $start+1);
        $this->addFilter('orderby', 'relevance');
    
        $items = parent::items(0, $limit);
        return $items;
    }
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['PLAYLIST']) && strlen($args['PLAYLIST'])) {
            $this->playlist = $args['PLAYLIST'];
        }

        $this->setStandardFilters();
    }
    
    public function items($start=0, $limit=null) {
    
        $this->addFilter('max-results', $limit);
        $this->addFilter('start-index', $start+1);
                
        $items = parent::items(0, $limit);
        return $items;
    }

    protected function isValidID($id) {
        return preg_match("/^[A-Za-z0-9_-]+$/", $id);
    }
    
	 // retrieves video based on its id
	public function getItem($id)
	{
	    if (!$this->isValidID($id)) {
	        return false;
	    }
        $this->setBaseUrl("http://gdata.youtube.com/feeds/mobile/videos/$id");
        $this->addFilter('alt', 'jsonc'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2

        return $this->getParsedData();
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
        $video->setMobileURL($entry['content']['1']);
        $video->setTitle($entry['title']);
        $video->setDescription($entry['description']);
        $video->setDuration($entry['duration']);
        $video->setID($entry['id']);
        $video->setImage($entry['thumbnail']['sqDefault']);
        $video->setStillFrameImage($entry['thumbnail']['hqDefault']);
        
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
}
