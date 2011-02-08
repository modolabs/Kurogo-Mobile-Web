<?php

 class SiteVideobrightModule extends Module
 {
 	
   protected $id='videobright';  // this affects which .ini is loaded
   
   protected function initializeForPage() {

     $controller = BrightcoveDataController::factory();

	 $playerid = $this->getModuleVar('playerId');
	 $accountid = $this->getModuleVar('accountId');
	 $q = $this->getModuleVar('SEARCH_QUERY');
			
     switch ($this->page)
     {
        case 'index':
        	
        	 //search for videos
			 //$items = $controller->search($this->getModuleVar('SEARCH_QUERY'));
             $items = $controller->latest($accountid);

             $videos = array();

             //prepare the list
             foreach ($items as $video) {
             	
             	$prop_titleid  = $video->getProperty('bc:titleid');  // case-insensitive
             	$prop_playerid  = $video->getProperty('bc:playerid');  // FIXME why null?
             	$prop_accountid = $video->getProperty('bc:accountid');
             	
             	$prop_thumbnail = $video->getLink();
             	//$prop_thumbnail = $video->getProperty('media:thumbnail');  // FIXME Call to a member function value() on a non-object
             	//$attr_url = $prop_thumbnail->getAttr("url");
             	
             	
             	$videos[] = array(
			        'titleid'=>$prop_titleid,
			        'playerid'=>$playerid,
			        'title'=>$video->getTitle(),
             	
			        'img'=>$video->getImage(),
			        //'img'=>$attr_url,  // TODO
			        
			        'url'=>$this->buildBreadcrumbURL('detail', array(
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
        case 'detail':
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
 }