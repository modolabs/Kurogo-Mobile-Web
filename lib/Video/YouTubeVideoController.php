<?php

 class YouTubeVideoController extends DataController
 {
    protected $DEFAULT_PARSER_CLASS='YouTubeDataParser';
    protected $cacheFolder='Video';
    protected $cacheFileSuffix='json';
    
    public function search($q, $start=0, $limit=null) {
    
        $this->addFilter('q', $q); //set the query
        $this->addFilter('max-results', $limit);
        $this->addFilter('start-index', $start+1);
        $this->addFilter('orderby', 'relevance');
    
        $items = parent::items(0, $limit);
        return $items;
    }
    
    protected function init($args) {
        parent::init($args);

        $this->setBaseUrl('http://gdata.youtube.com/feeds/mobile/videos');
        $this->addFilter('alt', 'jsonc'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2
        $this->addFilter('orderby', 'published');
        
        if (isset($args['TAG'])) {
            $this->addFilter('category', $args['TAG']);
        }
        
        if (isset($args['AUTHOR'])) {
            $this->addFilter('author', $args['AUTHOR']);
        }
    }
    
    public function items($start=0, $limit=null) {
    
        $this->addFilter('max-results', $limit);
        $this->addFilter('start-index', $start+1);
                
        $items = parent::items(0, $limit);
        return $items;
    }
    
	 // retrieves video based on its id
	public function getItem($id)
	{
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
        $video = new YouTubeVideoObject();
        $video->setURL($entry['player']['default']);
        $video->setTitle($entry['title']);
        $video->setDescription($entry['description']);
        $video->setDuration($entry['duration']);
        $video->setID($entry['id']);
        $video->setImage($entry['thumbnail']['sqDefault']);
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
            } elseif (isset($data['data'])) {
                $video = $this->parseEntry($data['data']);
                return $video;
            } else {
                Debug::die_here($data);
                return array();
            }
        } 

        return array();
        
    }
}

class YouTubeVideoObject extends VideoObject
{
    protected $type = 'youtube';
}