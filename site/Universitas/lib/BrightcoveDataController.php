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
         // set the base url to YouTube
         $this->setBaseUrl('http://link.brightcove.com/services/mrss/player1459151488/270881183/new');
         //$this->addFilter('alt', 'json'); //set the output format to json
         //$this->addFilter('q', $q); //set the query
         //$this->addFilter('format', 6); //only return mobile videos
         //$this->addFilter('v', 2); // version 2

         //$data = $this->getParsedData();
         $data = $this->items(0,null,$total);
         
         //print_r($data);
          
         // $results = $data['feed']['entry'];
         //$results = $data['rss']['channel']['item'];
         //$results = $data->getItem();
         //$results = $data[0];
         $results = $data;

         return $results;
     }

	 // retrieves a YouTube Video based on its video id
	public function getItem($id)
	{
	    $this->setBaseUrl("http://link.brightcove.com/services/mrss/player1459151488/270881183/$id");
	    //$this->addFilter('alt', 'json'); //set the output format to json
	    //$this->addFilter('format', 6); //only return mobile videos
	    //$this->addFilter('v', 2); // version 2
	
         //$data = $this->getParsedData();

	    ////////////////
	    // IG: copy from RSSDataController
	    
	    $data = $this->items();
          
        foreach ($items as $item) {
            if ($item->getGUID()==$id) {
                return $item;
            }
        }
        
        return null;
	    ////////////////
        
         
	    ////return isset($data['entry']) ? $data['entry'] : false;
	    //return isset($data['item']) ? $data['item'] : false;
	}

 }