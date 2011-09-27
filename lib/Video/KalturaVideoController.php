<?php

    //require_once("KalturaClient.php");  // FIXME
    
 class KalturaVideoController extends VideoDataController
 {
 	
    protected $DEFAULT_PARSER_CLASS='KalturaDataParser';
    protected $token;
    protected $category;
    protected $partnerUserID;
    
    
    private function setStandardFilters() {
        $this->removeAllFilters();
	    $this->addFilter('token', $this->token);

	    if ($this->category) {
	        //$this->addFilter('all', 'tag:' . $this->category);
	    }
    }
    
    public function search($q, $start=0, $limit=10) {

        $this->setStandardFilters();
	    
        // #1
        //$this->addFilter('service', 'media');
	    //$this->addFilter('action', 'list');
	    // #2
	    $this->addFilter('service', 'search');
	    $this->addFilter('action', 'search');
	    
	    
	    $this->addFilter('search:keyWords', $q);
	    //$this->addFilter('search:searchSource', );
	    
	    
	    //$this->addFilter('all',$q); // uh oh. if there is a tag, then we have a problem since "all" will be overwritten
	    
	    $this->addFilter('pager:pageSize', $limit);
	    $this->addFilter('pager:pageIndex', floor($start / $limit));
	    
        $items = parent::items(0, $limit);
        return $items;
    }

    // TODO may either manually create session and requests or use Kaltura api client
    public function searchKalturaApi($q, $start=0, $limit=10) {
    
        $pager = new KalturaFilterPager();
        $pager->pageIndex = $start;
        $pager->pageSize = $limit;
        $tag = "";
        $filter = new KalturaMediaEntryFilter();
        $filter->tagsAdminTagsMultiLikeOr = $tag; // The listing will retrieve content with this ADMIN-TAGS. You can change the filter however you like
        $filter->orderBy = CREATED_AT_DESC;
        
        // retrieve the information from Kaltura
        $entries = $client->media->listAction($filter, $pager);
        if (!$entries)
        {
        	$entries = array();
        }
        
    }
    
    protected function init($args) {
    	
        parent::init($args);
        
        if (!isset($args['PartnerID'])) {
            throw new KurogoConfigurationException('Kaltura PartnerID not included');
        }
        $this->token = $args['token'];

        if (!isset($args['PartnerSecret'])) {
            throw new KurogoConfigurationException('Kaltura PartnerSecret not included');
        }        
        
        define("KALTURA_PARTNER_ID", PartnerID);
        define("KALTURA_PARTNER_ADMIN_SECRET", PartnerSecret);
        
        //define session variables
        $this->partnerUserID          = 'ANONYMOUS';
        
        //construct Kaltura objects for session initiation
        $config           = new KalturaConfiguration(KALTURA_PARTNER_ID);
        $client           = new KalturaClient($config);
        $ks               = $client->session->start(KALTURA_PARTNER_ADMIN_SECRET, $this->partnerUserID, KalturaSessionType::ADMIN);
        $client->setKs($ks);
       
       
	    $this->setBaseURL("http://www.kaltura.com/api_v3");
    }
    
    public function items($start=0, $limit=null) {
        $this->setStandardFilters();
        //$this->addFilter('service', 'media');
	    //$this->addFilter('action', 'list');
	    $this->addFilter('pager:pageSize', $limit);
	    $this->addFilter('pager:pageIndex', floor($start / $limit));
        $items = parent::items(0, $limit);
        return $items;
    }
    
	 // retrieves video based on its id
	public function getItem($id) {
	
        $this->setStandardFilters();
	    //$this->addFilter('command', 'find_video_by_id');
	    //$this->addFilter('video_id', $id);
        return $this->getParsedData();
	}
}
 //class KalturaDataParser extends DataParser
class KalturaDataParser extends RSSDataParser
{
	
    protected function parseEntry($entry) {
        $video = new KalturaVideoObject();
    
   		// "Media" object to video
   		
        $video->setID($entry->id);
        $video->setURL($entry->downloadUrl);
        $video->setTitle($entry->name);
        
        $video->setDescription($entry->description);
       
        
        //duration in milliseconds
        $video->setDuration(floor($entry->duration/1000));
        
        //date in milliseconds
        $published = new DateTime('@' . floor($entry->updateAt / 1000));
        $video->setPublished($published);
        
        $video->setImage($entry->thumbnailUrl);
        
        return $video;
    }
    
    public function parseData($data) {
    	
    	$data = parent::parseData($data);
    	
		// TODO?    	

        return array();
    }
}

class KalturaVideoObject extends VideoObject
{
    protected $type = 'Kaltura';
}