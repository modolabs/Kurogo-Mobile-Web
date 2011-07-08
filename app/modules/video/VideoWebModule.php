<?php

Kurogo::includePackage('Video');

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
    
    protected function getDefaultSection() {
        return key($this->feeds);
    }

    public function linkForItem(VideoObject $video, $data=null) {
    
        $options = array(
            'videoid'=>$video->getID()
        );
        
        foreach (array('section','filter') as $field) {
            if (isset($data[$field])) {
                $options[$field] = $data[$field];
            }
        }
        
        $addBreadcrumb = isset($data['addBreadcrumb']) ? $data['addBreadcrumb'] : true;
        $noBreadcrumbs = isset($data['noBreadcrumbs']) ? $data['noBreadcrumbs'] : false;

        if ($noBreadcrumbs) {
          $url = $this->buildURL('detail', $options);
        } else {
          $url = $this->buildBreadcrumbURL('detail', $options, $addBreadcrumb);
        }

        $desc = $video->getDescription();
        if (isset($data['federatedSearch']) && $data['federatedSearch']) {
            $subtitle = '';
        } else {
            $subtitle = "(" . VideoModuleUtils::getDuration($video->getDuration()) . ") " . $desc;
        }

        return array(
            'url'=>$url,
            'title'=>$video->getTitle(),
            'subtitle'=>$subtitle,
            'imgWidth'=>120,  
            'imgHeight'=>100,  
            'img'=>$video->getImage()
        );
    }
    
    public function searchItems($searchTerms, $limit=null, $options=null) {
        
        $section = isset($options['section']) ? $options['section'] : $this->getDefaultSection();
        $controller = $this->getFeed($section);
                
      	$items = $controller->search($searchTerms, 0, $limit);
      	return $items;
    }
    
    protected function getFeed($feed=null) {
        $feed = isset($this->feeds[$feed]) ? $feed : $this->getDefaultSection();
        $feedData = $this->feeds[$feed];
        
        $controller = DataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
        return $controller;
    }
    
    protected function initializeForPage() {
   
        if ($this->pagetype=='basic') {
            return;
        }
        
        if (count($this->feeds)==0) {
            throw new Exception("No video feeds configured");
        }
    
        // Categories / Sections
        
        $section = $this->getArg('section', $this->getDefaultSection());
        $this->assign('currentSection', $section);
        $this->assign('sections'      , VideoModuleUtils::getSectionsFromFeeds($this->feeds));
        
        $controller = $this->getFeed($section);
        
        switch ($this->page)
        {  
              case 'pane':
                $start = 0;
                $maxPerPage = $this->getOptionalModuleVar('MAX_PANE_RESULTS', 5);
                $data = array(
                    'noBreadcrumbs'=>true,
                    'section'=>$section
                );

                $items = $controller->items($start, $maxPerPage);
                $videos = array();

                foreach ($items as $video) {
                    $videos[] = $this->linkForItem($video, $data);
                }
                
                $this->assign('videos', $videos);
                break;
            case 'search':
            case 'index':
        
                $maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
        	    $start = $this->getArg('start', 0);
        	    
                if ($this->page == 'search') {
                    if ($filter = $this->getArg('filter')) {
                        $searchTerms = trim($filter);
                        $controller->search($searchTerms, $start, $maxPerPage);
                        $this->assign('searchTerms', $searchTerms);
                    } else {
                        $this->redirectTo('index', array('section'=>$section), false);
                    }
                } else {
                     $items = $controller->items($start, $maxPerPage);
                }
                             
                $totalItems = $controller->getTotalItems();
                $videos = array();
                foreach ($items as $video) {
                    $videos[] = $this->linkForItem($video, array('section'=>$section));
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
                 
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->generateBookmarkLink();
                }
                    
                break;
 
            case 'bookmarks':
            	
                if (!$this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->redirectTo('index');
                }
                
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
                    $this->assign('ajax'      ,       $this->getArg('ajax', null));
                    $this->assign('videoTitle',       $video->getTitle());
                    $this->assign('videoid',          $video->getID());
                    $this->assign('videoDescription', $video->getDescription());
                    $this->assign('videoAuthor'     , $video->getAuthor());
                    $this->assign('videoDate'       , $video->getPublished()->format('M n, Y'));
                    
                    $body = $video->getDescription() . "\n\n" . $video->getURL();

                    if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
                        $this->assign('shareEmailURL',    $this->buildMailToLink("", $video->getTitle(), $body));
                        $this->assign('videoURL',         $video->getURL());
                        $this->assign('shareRemark',      $video->getTitle());
                    }
    
                      // Bookmark
                    if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                      $cookieParams = array(
                        'section' => $section,
                        'title'   => $video->getTitle(),
                        'videoid' => $videoid
                      );
    
                      $cookieID = http_build_query($cookieParams);
                      $this->generateBookmarkOptions($cookieID);
                    }
    
    
                } else {
                    $this->redirectTo('index', array('section'=>$section),false);
                }
                break;
        }
    }
    
 }