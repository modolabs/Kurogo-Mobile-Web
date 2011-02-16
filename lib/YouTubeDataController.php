<?php

 class YouTubeDataController extends DataController
 {
     protected $cacheFolder = "Videos"; // set the cache folder
     protected $cacheSuffix = "json";   // set the suffix for cache files
     protected $DEFAULT_PARSER_CLASS='JSONDataParser';

     public static function factory($args=null)
     {
         $args['CONTROLLER_CLASS'] =  __CLASS__;
         $args['PARSER_CLASS'] =  'JSONDataParser';
         $controller = parent::factory($args);
         return $controller;
     }

     public function search($q)
     {
         // set the base url to YouTube
         $this->setBaseUrl('http://gdata.youtube.com/feeds/mobile/videos');
         $this->addFilter('alt', 'json'); //set the output format to json
         $this->addFilter('q', $q); //set the query
         $this->addFilter('format', 6); //only return mobile videos
         $this->addFilter('v', 2); // version 2

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