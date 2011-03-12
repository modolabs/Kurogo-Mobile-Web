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
    
        $items = parent::items(0, $limit);
        return $items;
    }
    
    protected function init($args) {
        parent::init($args);

        $this->setBaseUrl('http://gdata.youtube.com/feeds/mobile/videos');
        $this->addFilter('alt', 'json'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2
        
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
        $this->addFilter('alt', 'json'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2

        return $this->getParsedData();
	}
}
 
class YouTubeDataParser extends DataParser
{
    protected function parseEntry($entry) {
        $video = new YouTubeVideoObject();
        $video->setURL($entry['link'][0]['href']);
        $video->setTitle($entry['title']['$t']);
        $video->setDescription($entry['media$group']['media$description']['$t']);
        $video->setDuration($entry['media$group']['yt$duration']['seconds']);
        $video->setID($entry['media$group']['yt$videoid']['$t']);
        $video->setImage($entry['media$group']['media$thumbnail'][0]['url']);
        
        if ($entry['media$group']['media$keywords']) {
            $tags = explode(', ', $entry['media$group']['media$keywords']['$t']);
            $video->setTags($tags);
        }
        $video->setAuthor($entry['author'][0]['name']['$t']);
        $published = new DateTime($entry['published']['$t']);
        $video->setPublished($published);
        return $video;
    }
    
    public function parseData($data) {
        if ($data = json_decode($data, true)) {
            
            if (isset($data['feed']['entry'])) {
                $videos = array();  
                $this->setTotalItems($data['feed']['openSearch$totalResults']['$t']);

                foreach ($data['feed']['entry'] as $entry) {
                    $videos[] = $this->parseEntry($entry);
                }
                
                return $videos;
            } elseif (isset($data['entry'])) {
                $video = $this->parseEntry($data['entry']);
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
}