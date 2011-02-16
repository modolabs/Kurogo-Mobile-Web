<?php

 class VideoModule extends Module
 {
 	
   protected $id='video';  // this affects which .ini is loaded
   
   protected function initializeForPage() {

	 $brightcove_or_youtube = $this->getModuleVar('brightcove_or_youtube');
	 
	 if ($brightcove_or_youtube==1) {
        $controller = DataController::factory('BrightcoveDataController');
	 	//$controller = BrightcoveDataController::factory();
	 	$this->handleBrightcove($controller);
	 } else {
        $controller = DataController::factory('YouTubeDataController');
     	//$controller = YouTubeDataController::factory();
     	$this->handleYoutube($controller);
     }
     
   }
   
  protected function handleBrightcove($controller) {
  	
	 $playerid  = $this->getModuleVar('playerId');
	 $accountid = $this->getModuleVar('accountId');
	 
     switch ($this->page)
     {
        case 'index':
        	
        	 //search for videos
			 //$items = $controller->search($this->getModuleVar('SEARCH_QUERY'));
             $items = $controller->latest($accountid);

             $videos = array();

             //prepare the list
             foreach ($items as $video) {
             	
             	$prop_titleid  = $video->getProperty('bc:titleid');  
             	$prop_playerid  = $video->getProperty('bc:playerid');  // FIXME why null?
             	$prop_accountid = $video->getProperty('bc:accountid');
             	
             	//$link = $video->getLink();  // FIXME blank?
             	//$img = $video->getImage();  // also blank
             	
             	$prop_thumbnail = $video->getProperty('media:thumbnail');  
             	if (is_array($prop_thumbnail)) {
             		$attr_url = $prop_thumbnail[0]->getAttrib("URL"); 
             	} else {
             		$attr_url = ""; 
             		//$attr_url = $prop_thumbnail->getAttrib("URL"); // FIXME
             	}
             	
             	$videos[] = array(
			        'titleid'=>$prop_titleid,
			        'playerid'=>$playerid,
			        'title'=>$video->getTitle(),
			        'img'=>$attr_url,  
			        //'url'=>$this->buildBreadcrumbURL('detail', array(
			        'url'=>$this->buildBreadcrumbURL('detail-brightcove', array(
			            'videoTitle'=>$video->getTitle(),
			            'videoDescription'=>$video->getDescription(),
			            'videoid'=>$prop_titleid,
			            'playerid'=>$playerid,
			            'accountid'=>$prop_accountid
             		))
             		
             	);
             }
             
             $this->assign('videos', $videos);
             break;
        case 'detail-brightcove':
			   $videoid = $this->getArg('videoid');
			   
			   // IG: do we really need to query again?
			   // #1
			   /*
			   if ($video = $controller->getItem($videoid)) {
			      $this->assign('videoid', $videoid);
			      //$this->assign('playerid', $playerid);
			      //$this->assign('accountid', $accountid);
			      //$this->assign('videoTitle', $title);
			      $this->assign('videoLink', $video->getLink());  // UNUSED
			      $this->assign('videoDescription', $video->getDescription());
			   } else {
			      $this->redirectTo('index');
			   }
			   */
			   // #2
			    $this->assign('playerid', $playerid);
			    $this->assign('videoid', $videoid);
			    $this->assign('accountid', $this->getArg('accountid'));
			    $this->assign('videoTitle', $this->getArg('videoTitle'));
			    $this->assign('videoDescription', $this->getArg('videoDescription'));
			   
			   break;     
     }
  } 
   
  protected function handleYouTube($controller) {
  	
	 $q = $this->getModuleVar('SEARCH_QUERY');
	 
   switch ($this->page)
     {
        case 'index':
        	
        	
        	//search for videos
			$items = $controller->search($this->getModuleVar('SEARCH_QUERY'));
             //$items = $controller->search('mobile web');
             $videos = array();

             //prepare the list
             foreach ($items as $video) {
             	$videos[] = array(
			        'title'=>$video['title']['$t'],
			        'img'=>$video['media$group']['media$thumbnail'][0]['url'],
			        'url'=>$this->buildBreadcrumbURL('detail-youtube', array(
			            'videoid'=>$video['media$group']['yt$videoid']['$t']
             		))
             	);
             }

             $this->assign('videos', $videos);
             break;
        case 'detail-youtube':
			   $videoid = $this->getArg('videoid');
			   if ($video = $controller->getItem($videoid)) {
			      $this->assign('videoid', $videoid);
			      $this->assign('videoTitle', $video['title']['$t']);
			      $this->assign('videoDescription', $video['media$group']['media$description']['$t']);
			   } else {
			      $this->redirectTo('index');
			   }
			   break;     
     }
  } 
   
 }