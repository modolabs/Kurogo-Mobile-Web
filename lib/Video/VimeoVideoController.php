<?php

 class VimeoVideoController extends VideoDataController
 {
    protected $DEFAULT_PARSER_CLASS='VimeoDataParser';
    protected $channel;
    
    protected function init($args) {
        parent::init($args);

        if (isset($args['CHANNEL']) && strlen($args['CHANNEL'])) {
            $this->channel = $args['CHANNEL'];
        }
    }
    
    public function search($q, $start=0, $limit=null) {
    
        $this->setBaseURL($this->getVimeoBaseURL());
        $_items = parent::items($start, $limit);
        $items = array();
        //vimeo does not have a search api in its "standard" api
        foreach ($_items as $video) {
            if ( (stripos($video->getDescription(), $q)!==FALSE) || (stripos($video->getTitle(), $q)!==FALSE)) {
                $items[] = $video;
            }
        }
        
        $this->setTotalItems(count($items));
        
        return $this->limitItems($items, $start, $limit);
    }
    
    private function getVimeoBaseURL() {
        $url = 'http://vimeo.com/api/v2/';
        
        if ($this->author) {
            $url .= $this->author;
        } elseif ($this->channel) {
            $url .= 'channel/' . $this->channel;
        } else {
            throw new KurogoConfigurationException("Unable to determine type of request");
        }
        
        $url .= "/videos.json";
        return $url;
    }
    
    public function items($start=0, $limit=null) {
    
        $this->setBaseURL($this->getVimeoBaseURL());
        $items = parent::items($start, $limit);
        return $items;
    }
        
    protected function isValidID($id) {
        return preg_match("/^[0-9]+$/", $id);
    }
    
	 // retrieves video based on its id
	public function getItem($id) {
	    if (!$this->isValidID($id)) {
	        return false;
	    }

        $url = 'http://vimeo.com/api/v2/video/' . $id . '.json';
        $this->setBaseURL($url);
        if ($items = $this->getParsedData()) {
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
}