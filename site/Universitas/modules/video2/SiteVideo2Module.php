<?php

 class SiteVideo2Module extends Module
 {
   protected $id='video';
   protected function initializeForPage() {
     //instantiate controller
     $controller = BrightcoveDataController::factory();

     switch ($this->page)
     {
        case 'index':
        	
        	//search for videos
			$items = $controller->search($this->getModuleVar('SEARCH_QUERY'));
             //$items = $controller->search('mobile web');
             
             $videos = array();

             //prepare the list
             /*
             foreach ($items as $video) {
             	$videos[] = array(
             	
			        'titleid'=>$video['bc$titleid']['$ti'],
			        'playerid'=>$video['bc$playerid']['$tp'],
             	
			        'title'=>$video['title']['$t'],
			        'img'=>$video['media$group']['media$thumbnail'][0]['url'],
			        'url'=>$this->buildBreadcrumbURL('detail', array(
			            'videoid'=>$video['media$group']['yt$videoid']['$t']
             		))
             	);
             }
			*/
     
             foreach ($items as $video) {
             	
             	print_r($video);
             	
             	$videos[] = array(
			        'title'=>$video->getTitle(),
			        'img'=>$video->getImage()
             	);
             }
             
             $this->assign('videos', $videos);
             break;
        case 'detail':
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