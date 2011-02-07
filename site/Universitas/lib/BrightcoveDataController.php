<?php

 class BrightcoveDataController extends DataController
 {
     protected $cacheFolder = "Videos"; // set the cache folder
     protected $cacheSuffix = "xml";   // set the suffix for cache files

     public static function factory($args=null)
     {
         $args['CONTROLLER_CLASS'] =  __CLASS__;
         $args['PARSER_CLASS'] =  'RSSDataParser';
         $controller = parent::factory($args);
         return $controller;
     }

     public function search($q)
     {
     	
     	// TODO add search
     	//$this->setBaseUrl("http://api.brightcove.com/services/library?command=search_videos&video_fields=name,shortDescription&page_size=3&get_item_count=true&sort_by=MODIFIED_DATE:DESC&token=$token");
	    
     	// TODO use $accountid and $playerid
         //$this->setBaseUrl('http://link.brightcove.com/services/mrss/player$playerid/$accountid/new');
         $url = "http://link.brightcove.com/services/mrss/$q/new";
         $this->setBaseUrl($url);  // TEMP
     	
         //$data = $this->getParsedData();
         $data = $this->items(0,null,$total);
         

         //$results = $data[0];
         $results = $data;

         return $results;
     }

	 // retrieves a Brightcove Video based on its video id
	public function getItem($id)
	{
		// FIXME
	    //$this->setBaseUrl("http://link.brightcove.com/services/mrss/player$playerid/$accountid/$titleid");
	    //$this->setBaseUrl($id);
	    $url = "http://api.brightcove.com/services/library?command=search_videos&video_fields=name,shortDescription&page_size=3&get_item_count=true&sort_by=MODIFIED_DATE:DESC&token=$id";
		$this->setBaseUrl("http://api.brightcove.com/services/library?command=search_videos&video_fields=name,shortDescription&page_size=3&get_item_count=true&sort_by=MODIFIED_DATE:DESC&token=$id");
	    
	    
	    
	    $data = $this->items(0,null,$total);   // IG: copy from RSSDataController
          
        foreach ($data as $item) {
        	
        	echo $item->getGUID();
        	//print_r($item->getGUID());  // FIXME difference?
        	
            if ($item->getGUID()==$id) {
                return $item;
            }
        }
        
        return null;
	    
	    //return isset($data['item']) ? $data['item'] : false;
	}

 }