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
     	
     	// TODO use $accountid and $playerid
     	
         // set the base url to Brightcove
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

	 // retrieves a Brightcove Video based on its video id
	public function getItem($id)
	{
		// FIXME
	    $this->setBaseUrl("http://link.brightcove.com/services/mrss/player$playerid/$accountid/$titleid");
	    //$this->setBaseUrl($id);
	   
	    //http://localhost:8888/video2/video2/kurogo/Kurogo-Mobile-Web/web/detail?
	    //videoid=34459290001&playerid=1459151488&accountid=270881183&_b=%258B%258E%2505%2500
	    
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