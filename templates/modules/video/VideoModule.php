<?php

 class VideoModule extends Module
 {
 	
   protected $id='video';  // this affects which .ini is loaded
   
   protected function initializeForPage() {
   
     if ($GLOBALS['deviceClassifier']->getPagetype()=='basic') {
	     $this->assign('showUnsupported', true);
	     return;
     }
     
   	 $doSearch = $this->getModuleVar('search');
     if ($doSearch==1) $this->assign('doSearch', $doSearch);
         
	 $brightcove_or_youtube = $this->getModuleVar('brightcove_or_youtube');
	 
	 if ($brightcove_or_youtube==1) {
        $controller = DataController::factory('BrightcoveDataController');
	 	$this->handleBrightcove($controller);
	 } else {
        $controller = DataController::factory('YouTubeDataController');
     	$this->handleYoutube($controller);
     }
     
   }
   
  protected function handleBrightcove($controller) {
  	  
	 $brightcoveToken  = $this->getModuleVar('brightcoveToken');
     $this->assign('token', $brightcoveToken);
             
	 $playerid  = $this->getModuleVar('playerId');
	 $accountid = $this->getModuleVar('accountId');
	 
     switch ($this->page)
     {
     	    
        case 'search':
	        if ($filter = $this->getArg('filter')) {
	          $searchTerms = trim($filter);
	          
        	  //search for videos
			  $items = $controller->search($searchTerms,$brightcoveToken);
			 
			  if ($items !== false) {
			  	  // TODO handle 0 or 1 result
            	  $resultCount = count($items);
             	  $videos = array();
			  }
			  
	        }
	        break;
        case 'index':
        	
             $items = $controller->latest($accountid);

             $videos = array();

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
     
     if (isset($videos)) {
     	
             //prepare the list
             foreach ($items as $video) {
             	
             	$prop_titleid  = $video->getProperty('bc:titleid');  
             	$prop_playerid  = $video->getProperty('bc:playerid');  // FIXME why null?
             	$prop_accountid = $video->getProperty('bc:accountid');
             	$prop_length = $video->getProperty('bc:duration');
             	
             	
             	$prop_thumbnail = $video->getProperty('media:thumbnail');  
             	if (is_array($prop_thumbnail)) {
             		$attr_url = $prop_thumbnail[0]->getAttrib("URL"); 
             	} else {
             		if ($prop_thumbnail) {
             			$attr_url = $prop_thumbnail->getAttrib("URL");
             		} else {
             			//$attr_url = "/common/images/placeholder-image.png";  // TODO
             			$attr_url = "/common/images/title-video.png";
             		}
             	}
             	
             	$desc = $video->getDescription();
             	if (strlen($desc)>75) {
             		$desc = substr($desc,0,75) . "...";
             	}
             	
             	$duration = $this->getDuration($prop_length);
             	
             	$subtitle = $desc . "<br/>" . $duration;
             	
             	$videos[] = array(
			        'titleid'=>$prop_titleid,
			        'playerid'=>$playerid,
			        'title'=>$video->getTitle(),
			        'subtitle'=>$subtitle,
			        'img'=>$attr_url,  
			        'imgWidth'=>100,  
			        'imgHeight'=>80,  
			        'url'=>$this->buildBreadcrumbURL('detail-brightcove', array(
			            'videoTitle'=>$video->getTitle(),
			            'videoDescription'=>$desc,
			            'videoid'=>$prop_titleid,
			            'playerid'=>$playerid,
			            'accountid'=>$prop_accountid
             		))
             		
             	);
             }
             
             // TODO
	         //$this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
	         //$this->addOnLoad('setupVideosListing();');
        
             $this->assign('videos', $videos);
     }
     
  } 

  protected function getDuration($prop_length) {
  	if (!$prop_length) {
  		return "";
  	} elseif ($prop_length<60) {
  		return $prop_length . " secs";
    } else {
        $mins = intval($prop_length / 60);
        $secs = $prop_length % 60;
        return $mins . " mins, " . $secs . " secs";
    }
  }
  
  protected function handleYouTube($controller) {
  	
   switch ($this->page)
     {
     	    
        case 'search':
	        if ($filter = $this->getArg('filter')) {
	          $searchTerms = trim($filter);
			  $items = $controller->search($searchTerms);
              $videos = array();
	        }
	    	break;
	          
        case 'index':
        	 // default search 
			 $items = $controller->search($this->getModuleVar('SEARCH_QUERY'));
             $videos = array();
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
     
     
     if (isset($videos)) {

             foreach ($items as $video) {
             
             	$desc = $video['media$group']['media$description']['$t'];
             	if (strlen($desc)>75) {
             		$desc = substr($desc,0,75) . "...";
             	}
             	
             	$duration = $video['media$group']['yt$duration']['seconds'];
             	
             	$duration = $this->getDuration($duration);
             	
             	$subtitle = $desc . "<br/>" . $duration;
             	
             	$videos[] = array(
			        'title'=>$video['title']['$t'], 
			        'subtitle'=>$subtitle,
			        'imgWidth'=>100,  
			        'imgHeight'=>80,  
			        'img'=>$video['media$group']['media$thumbnail'][0]['url'],
			        'url'=>$this->buildBreadcrumbURL('detail-youtube', array(
			            'videoid'=>$video['media$group']['yt$videoid']['$t']
             		))
             	);
             }

             $this->assign('videos', $videos);
     }
     
  } 
   
 }