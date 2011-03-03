<?php

 class VideoJsonDataController extends DataController
 {
     protected $cacheFolder = "Videos"; // set the cache folder
     protected $cacheSuffix = "json";   // set the suffix for cache files
     protected $DEFAULT_PARSER_CLASS='JSONDataParser';
	 public $totalItems;

     public static function factory($args=null)
     {
         $args['CONTROLLER_CLASS'] =  __CLASS__;
         $args['PARSER_CLASS'] =  'JSONDataParser';
         $controller = parent::factory($args);
         return $controller;
     }

     public function search($q,$pageSize=20,$startIndex=1,$category="",$token=null,$bright_or_youtube=true)
     {
     	
     	if ($bright_or_youtube) {
     		
	     	 $url = "http://api.brightcove.com/services/library?command=search_videos&output=json&video_fields=id,name,shortDescription,thumbnailURL&page_size=$pageSize&page_number=$startIndex&get_item_count=true&sort_by=MODIFIED_DATE:DESC&token=$token";
	     	 
	     	 $this->setBaseUrl($url);
         	 //$this->addFilter('token', $token); 
         	 //$this->addFilter('page_size', $pageSize); 
         	 //$this->addFilter('page_number', $startIndex); 
		 
	         $data = $this->items(0,null,$this->totalItems);
	         
	         return $data;    		
     		
     	}
     	
     	
     	
         // set the base url to YouTube
         $this->setBaseUrl('http://gdata.youtube.com/feeds/mobile/videos');
         $this->addFilter('alt', 'json'); //set the output format to json
         $this->addFilter('q', $q); //set the query
         $this->addFilter('format', 6); //only return mobile videos
         $this->addFilter('v', 2); // version 2
         $this->addFilter('max-results', $pageSize);
         $this->addFilter('start-index', $startIndex);

         $data = $this->getParsedData();
         $results = $data['feed']['entry'];

         return $results;
     }

	 // retrieves a YouTube Video based on its video id
	public function getItem($id)
	{
	    $this->setBaseUrl("http://gdata.youtube.com/feeds/mobile/videos/$id");
	    $this->addFilter('alt', 'json'); //set the output format to json
	    $this->addFilter('format', 6); //only return mobile videos
	    $this->addFilter('v', 2); // version 2
	
	    $data = $this->getParsedData();
	    return isset($data['entry']) ? $data['entry'] : false;
	}

 }