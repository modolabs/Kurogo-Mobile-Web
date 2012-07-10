<?php

 class BrightCoveRetriever extends URLDataRetriever implements SearchDataRetriever
 {
    protected $DEFAULT_PARSER_CLASS='BrightCoveDataParser';
    
    private function setStandardFilters() {
	    $this->setBaseURL("http://api.brightcove.com/services/library");
	    $this->addFilter('token', $this->getOption('token'));
	    $this->addFilter('output', 'json');
	    $this->addFilter('video_fields', 'id,name,shortDescription,longDescription,thumbnailURL,length,FLVURL,publishedDate,tags');
	    $this->addFilter('get_item_count', 'true');
	    $this->addFilter('sort_by', 'MODIFIED_DATE');
	    $this->addFilter('sort_order', 'DESC');
    }
    
    public function search($searchTerms, &$response=null) {

        $this->setStandardFilters();
	    $this->addFilter('command', 'search_videos');
	    $this->addFilter('all', $searchTerms); // uh oh. if there is a tag, then we have a problem since "all" will be overwritten
	    
	    return $this->getData($response);
    }
    
    protected function init($args) {
        parent::init($args);
        if (!isset($args['token'])) {
            throw new KurogoConfigurationException('Brightcove token not included');
        }
        $this->setOption('token', $args['token']);

        if (!isset($args['playerKey'])) {
            throw new KurogoConfigurationException('Brightcove playerKey not included');
        }
        $this->setOption('playerKey', $args['playerKey']);

        if (!isset($args['playerId'])) {
            throw new KurogoConfigurationException('Brightcove playerId not included');
        }
        $this->setOption('playerId', $args['playerId']);
        $this->setStandardFilters();
	    $this->addFilter('command', 'find_all_videos');
    }
    
    protected function initRequest() {
	    if ($tag = $this->getOption('tag')) {
	        $this->addFilter('all', 'tag:' . $tag);
	    }

        $start = $this->getOption('start');
        $limit = $this->getOption('limit');

	    if ($limit) {
            $this->addFilter('page_size', $limit) ;
            $this->addFilter('page_number', floor($start / $limit));
        }
    }
    
	 // retrieves video based on its id
	public function getItem($id) {
	
        $this->setStandardFilters();
	    $this->addFilter('command', 'find_video_by_id');
	    $this->addFilter('video_id', $id);
        return $this->getData();
	}
}
 
class BrightCoveDataParser extends DataParser
{
    protected function parseEntry($entry) {
        $video = new BrightCoveVideoObject();
        $video->setID($entry['id']);
        if (isset($entry['FLVURL'])) {
            $video->setURL($entry['FLVURL']);
        }
        $video->setTitle($entry['name']);
        if ($entry['longDescription']) {
            $video->setDescription($entry['longDescription']);
        } else {
            $video->setDescription($entry['shortDescription']);
        }
        //duration in milliseconds
        $video->setDuration(floor($entry['length']/1000));
        $video->setTags($entry['tags']);
        //date in milliseconds
        $published = new DateTime('@' . floor($entry['publishedDate'] / 1000));
        $video->setPublished($published);
        $video->setImage($entry['thumbnailURL']);
        return $video;
    }
    
    public function parseData($data) {
        if ($data = json_decode($data, true)) {
                    
            if (isset($data['items'])) {
                $videos = array();  
                $this->setTotalItems($data['total_count']);

                foreach ($data['items'] as $entry) {
                    $videos[] = $this->parseEntry($entry);
                }
                
                return $videos;
            } elseif (isset($data['id'], $data['name'])) {
                $video = $this->parseEntry($data);
                return $video;
            } else {
                return array();
            }
        } 

        return array();
    }
}

class BrightCoveVideoObject extends VideoObject
{
    protected $type = 'brightcove';
}