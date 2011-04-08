<?php

includePackage('Video');

class VideoWebModule extends WebModule
{
    protected $id='video';  // this affects which .ini is loaded
    protected $feeds = array();
    protected $bookmarkLinkTitle = 'Bookmarked Videos';
   
    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        return $this->buildBreadcrumbURL('detail', $params, true);
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        $titles = array($params['title']);
        if (isset($params['subtitle'])) {
            $titles[] = $params['subtitle'];
        }
        return $titles;
        
    }
    
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    }
    
    protected function getListItemForVideo(VideoObject $video, $section) {

        $listItemArray = VideoModuleUtils::getListItemForVideo($video, $section, $this);
        // Add breadcrumb.
        $listItemArray['url'] = $this->buildBreadcrumbURL('detail', array(
            'section'=>$section,
            'videoid'=>$video->getID()));
            
        return $listItemArray;
    }
    
    protected function initializeForPage() {
   
        if ($this->pagetype=='basic') {
            return;
        }
        
        if (count($this->feeds)==0) {
            throw new Exception("No video feeds configured");
        }
    
       
        // Categories / Sections
        
        $section = $this->getArg('section');

        if (!isset($this->feeds[$section])) {
            $section = key($this->feeds);
        }
        
        $feedData = $this->feeds[$section];
        $this->assign('currentSection', $section);
        $this->assign('sections'      , VideoModuleUtils::getSectionsFromFeeds($this->feeds));
        $this->assign('feedData'      , $feedData);
        
        $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);

        switch ($this->page)
        {  
            case 'search':
            case 'index':
        
                $maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
        	    $start = $this->getArg('start', 0);
        	    
                if ($this->page == 'search') {
                    if ($filter = $this->getArg('filter')) {
                        $searchTerms = trim($filter);
                        $items = $controller->search($searchTerms, $start, $maxPerPage);
                        $this->assign('searchTerms', $searchTerms);
                    } else {
                        $this->redirect('index', array('section'=>$section), false);
                    }
                } else {
                     $items = $controller->items($start, $maxPerPage);
                }
                             
                $totalItems = $controller->getTotalItems();
                $videos = array();
                foreach ($items as $video) {
                    $videos[] = $this->getListItemForVideo($video, $section);
                }
                
                $this->assign('videos', $videos);
                $this->assign('totalItems', $totalItems);
                
                $previousURL = null;
                $nextURL = null;
    
                if ($totalItems > $maxPerPage) {
                    $args = $this->args;
                 
                    if ($start > 0) {
                        $args['start'] = $start - $maxPerPage;
                        $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
                    }
                    
                    if (($totalItems - $start) > $maxPerPage) {
                        $args['start'] = $start + $maxPerPage;
                        $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
                    }		
                }
    
                $hiddenArgs = array(
                  'section'=>$section
                );
          
          		$this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
          		$this->addOnLoad('setupVideosListing();');
          
                $this->assign('start',       $start);
                $this->assign('previousURL', $previousURL);
                $this->assign('nextURL',     $nextURL);
                $this->assign('hiddenArgs',  $hiddenArgs);
                $this->assign('maxPerPage',  $maxPerPage);
                 
                $this->generateBookmarkLink();
                    
                break;
 
            case 'bookmarks':
            	
                $videos_bkms = array();

                foreach ($this->getBookmarks() as $aBookmark) {
                    if ($aBookmark) { // prevent counting empty string
                        $titles = $this->getTitleForBookmark($aBookmark);
                        $subtitle = count($titles) > 1 ? $titles[1] : null;
                        $videos_bkms[] = array(
                                'title' => $titles[0],
                                'subtitle' => $subtitle,
                                'url' => $this->detailURLForBookmark($aBookmark),
                        );
                    }
                }
                $this->assign('videos', $videos_bkms);
            
                break;
                
            case 'detail':
        
                $videoid = $this->getArg('videoid');
            
                if ($video = $controller->getItem($videoid)) {
                    $this->setTemplatePage('detail-' . $video->getType());
                    $this->assign('videoTitle',       $video->getTitle());
                    $this->assign('videoURL',         $video->getURL());
                    $this->assign('videoid',          $video->getID());
                    $this->assign('videoDescription', $video->getDescription());
                    $this->assign('videoAuthor'     , $video->getAuthor());
                    $this->assign('videoDate'       , $video->getPublished()->format('M n, Y'));
                    
                    $body = $video->getDescription() . "\n\n" . $video->getURL();
                    
                    $this->assign('shareEmailURL',    $this->buildMailToLink("", $video->getTitle(), $body));
                    $this->assign('videoURL',         $video->getURL());
                    $this->assign('shareRemark',      $video->getTitle());
    
                      // Bookmark
                      $cookieParams = array(
                        'section' => $section,
                        'title'   => $video->getTitle(),
                        'videoid' => $videoid
                      );
    
                      $cookieID = http_build_query($cookieParams);
                      $this->generateBookmarkOptions($cookieID);
    
    
                } else {
                    $this->redirectTo('index', array('section'=>$section),false);
                }
                break;
        }
    }
    
  public function federatedSearch($searchTerms, $maxCount, &$results) {
  	 
    $section = key($this->feeds);
    if (!$section) return 0;
    $feedData = $this->feeds[$section];
    if (!$feedData) return 0;
    $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);

  	$items = $controller->search($searchTerms, 0, $maxCount);
  	 
  	if ($items) {
  		$results = array();
  		foreach ($items as $video) {
  		    $listItem = $this->getListItemForVideo($video, $section);
  		    unset($listItem['subtitle']);
  			$results[] = $listItem;
  		}
  		return $controller->getTotalItems();
  	} else {
  		return 0;
  	}
  	
  }
  
 }