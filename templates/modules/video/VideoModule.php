<?php

 class VideoModule extends Module
 {
 	
   protected $id='video';  // this affects which .ini is loaded
   protected $maxPerPage = 10;
   protected $start = 0;
   protected $categories = 0;
   protected $feedIndex = 0;
   
   protected $totalItems;
   protected $tag;
   protected $brightcove_or_youtube;
   
   
   protected $brightcoveToken;
   protected $playerid;   // currently only used by Brightcove
   protected $playerKey;  // currently only used by Brightcove
   protected $accountid;  // currently only used by Brightcove
   
   protected function initializeForPage() {
   
   	if ($GLOBALS['deviceClassifier']->getPagetype()=='basic') {
   		$this->assign('showUnsupported', true);
   		return;
   	}

    if ($max = $this->getModuleVar('MAX_RESULTS')) {
    	$this->maxPerPage = $max;
    }

   
    // Categories / Sections
    
    $this->categories = $this->loadFeedData();
    
    $this->feedIndex = $this->getArg('section', 0);
    if (!isset($this->categories[$this->feedIndex])) {
      $this->feedIndex = 0;
    }
    
     $sections = array();
     foreach ($this->categories as $index => $feedData) {
          $sections[] = array(
            'value'    => $index,
            'tag'    => $feedData['TAG'],
            'selected' => ($this->feedIndex == $index),
            'code'      => $feedData['TAG_CODE']
          );
     }
    
     $this->assign('sections', $sections);
     
     
     // Handle videos
     
   	 $doSearch = $this->getModuleVar('search');
     if ($doSearch==1) $this->assign('doSearch', $doSearch);
         
	 $this->brightcove_or_youtube = $this->getModuleVar('brightcove_or_youtube');
	 if ($this->brightcove_or_youtube) {
		 $this->brightcoveToken  = $this->getModuleVar('brightcoveToken');
		 $this->playerKey = $this->getModuleVar('playerKey');
		 $this->playerid  = $this->getModuleVar('playerId');
		 $this->accountid = $this->getModuleVar('accountId');
	 } 
	 
	 $xml_or_json = $this->getModuleVar('xml_or_json');
	 if ($xml_or_json==1) {
        $controller = DataController::factory('VideoXMLDataController');
	 } else {
        $controller = DataController::factory('VideoJsonDataController');
     }
  
     	 
   switch ($this->page)
     {  
        case 'search':
	        if ($filter = $this->getArg('filter')) {
	          $searchTerms = trim($filter);
			  $items = $controller->search($searchTerms, 20, 1, "", $this->brightcoveToken,$this->brightcove_or_youtube);
			  
			  if ($items !== false) {
			  	  // TODO handle 0 or 1 result
            	  $resultCount = count($items);
             	  $videos = array();
			  }
	        }
	    	break;
	          
        case 'index':
        	
        	 // default search 
	         $searchTerms = "";
			 $items = $controller->search($searchTerms, 20, 1, "", $this->brightcoveToken, $this->brightcove_or_youtube);
			 
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
        case 'detail-brightcove':
			    $videoid = $this->getArg('videoid');
			    //$this->assign('flvurl', $this->getArg('flvurl'));
			    $this->assign('playerKey', $this->playerKey);
			    $this->assign('playerid', $this->playerid);
			    $this->assign('videoid', $videoid);
			    $this->assign('accountid', $this->accountid);
			    $this->assign('videoTitle', $this->getArg('videoTitle'));
			    $this->assign('videoDescription', $this->getArg('videoDescription'));
			   
     			/*
     			 * bright
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
			    
			   break;       
     }

     
     $this->totalItems = 0;
	 $this->start = $this->getArg('start',0);
	 $this->tag = $this->categories[$this->feedIndex];
	 
	 
     if (isset($videos)) {
	     if ($xml_or_json==1) {
	     	// FIXME currently only support Brightcive - check here
		 	$this->handleBrightcoveRSS($controller,$videos,$items);
		 } else {
	 		if ($this->brightcove_or_youtube) $this->handleJSON($controller,$videos,$items['items']);
	 		else $this->handleJSON($controller,$videos,$items);
	     }
     }
     
     // Previous / Next
      
     $this->totalItems = $controller->totalItems;
      
     $previousURL = null;
     $nextURL = null;
     if ($this->totalItems > $this->maxPerPage) {
     	$args = $this->args;
     	if ($this->start > 0) {
     		//$args['start'] = --$start;
     		$args['start'] = $this->start - $this->maxPerPage;
     		$previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
     	}

     	if (($this->totalItems - $this->start) > $this->maxPerPage) {
     		//$args['start'] = ++$start;
     		$args['start'] = $this->start + $this->maxPerPage;
     		$nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
     	}
     }
      
     $this->assign('start', $this->start);
     $this->assign('previousURL', $previousURL);
     $this->assign('nextURL',     $nextURL);
     
     
   }
   
  protected function handleBrightcoveRSS($controller,$videos,$items) {
  	  
  	// TODO change to handleRSS() and add YouTube support
  	
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
			        'playerid'=>$this->playerid,
			        'title'=>$video->getTitle(),
			        'subtitle'=>$subtitle,
			        'class'=>'img',  // only applied to <a> ???
			        'img'=>$attr_url,  
			        'imgWidth'=>180,  
			        'imgHeight'=>120,  
			        'url'=>$this->buildBreadcrumbURL('detail-brightcove', array(
			            'videoTitle'=>$video->getTitle(),
			            'videoDescription'=>$desc,
			            'videoid'=>$prop_titleid,
			            'playerid'=>$this->playerid,
			            'accountid'=>$prop_accountid
             		))
             		
             	);
             }
             
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
  
  protected function handleJSON($controller,$videos,$items) {

     if (isset($videos)) {

             foreach ($items as $video) {
             
             	if ($this->brightcove_or_youtube) {
             		$flvurl  = $video['FLVURL'];
	             	$videoId = $video['id'];
	             	$img     = $video['thumbnailURL'];
	             	$title = $video['name'];
	             	$desc  = $video['shortDescription'];
	             	$duration = $video['length'] / 1000;  // millisecs
	             	$next = 'detail-brightcove';
             	} else {
	             	$desc = $video['media$group']['media$description']['$t'];
	             	if (strlen($desc)>75) {
	             		$desc = substr($desc,0,75) . "...";
	             	}
	             	
	             	$duration = $video['media$group']['yt$duration']['seconds'];
	             	$videoId = $video['media$group']['yt$videoid']['$t'];
	             	$img = $video['media$group']['media$thumbnail'][0]['url'];
	             	$title = $video['title']['$t'];
	             	$next = 'detail-youtube';
             	}
             	
             	$duration = $this->getDuration($duration);
             	
             	$subtitle = $desc . "<br/>" . $duration;
             	
             	$videos[] = array(
			        'title'=>$title, 
			        'subtitle'=>$subtitle,
			        'imgWidth'=>120,  
			        'imgHeight'=>100,  
			        'img'=>$img,
			        'url'=>$this->buildBreadcrumbURL($next, array(
			            'flvurl'=>$flvurl, 
			            'videoid'=>$videoId 
             		))
             	);
             }

             $this->assign('videos', $videos);
     }
     
  } 
   
 }