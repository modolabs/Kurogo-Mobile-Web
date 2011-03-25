<?php

includePackage('Video');

class VideoWebModule extends WebModule
{
    protected $id='video';  // this affects which .ini is loaded
    protected $feeds = array();
    protected $bookmarkCookie = 'videobookmarks';
    protected $bookmarkLifespan = 25237;
   
    // bookmarks -- copied from Maps
    protected function generateBookmarkOptions($cookieID) {
        // compliant branch
        $this->addOnLoad("setBookmarkStates('{$this->bookmarkCookie}', '{$cookieID}')");
        $this->assign('cookieName', $this->bookmarkCookie);
        $this->assign('expireDate', $this->bookmarkLifespan);
        $this->assign('bookmarkItem', $cookieID);

        // the rest of this is all touch and basic branch
        if (isset($this->args['bookmark'])) {
            if ($this->args['bookmark'] == 'add') {
                $this->addBookmark($cookieID);
                $status = 'on';
                $bookmarkAction = 'remove';
            } else {
                $this->removeBookmark($cookieID);
                $status = 'off';
                $bookmarkAction = 'add';
            }

        } else {
            if ($this->hasBookmark($cookieID)) {
                $status = 'on';
                $bookmarkAction = 'remove';
            } else {
                $status = 'off';
                $bookmarkAction = 'add';
            }
        }

        $this->assign('bookmarkStatus', $status);
        $this->assign('bookmarkURL', $this->bookmarkToggleURL($bookmarkAction));
        $this->assign('bookmarkAction', $bookmarkAction);
    }

    private function bookmarkToggleURL($toggle) {
        $args = $this->args;
        $args['bookmark'] = $toggle;
        return $this->buildBreadcrumbURL($this->page, $args, false);
    }

    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        return $this->buildBreadcrumbURL('detail', $params, true);
    }

    protected function getBookmarks() {
        $bookmarks = array();
        if (isset($_COOKIE[$this->bookmarkCookie])) {
            $bookmarks = explode(",", $_COOKIE[$this->bookmarkCookie]);
        }
        return $bookmarks;
    }

    protected function setBookmarks($bookmarks) {
        $values = implode(",", $bookmarks);
        $expireTime = time() + $this->bookmarkLifespan;
        setcookie($this->bookmarkCookie, $values, $expireTime);
    }

    protected function addBookmark($aBookmark) {
        $bookmarks = $this->getBookmarks();
        if (!in_array($aBookmark, $bookmarks)) {
            $bookmarks[] = $aBookmark;
            $this->setBookmarks($bookmarks);
        }
    }

    protected function removeBookmark($aBookmark) {
        $bookmarks = $this->getBookmarks();
        $index = array_search($aBookmark, $bookmarks);
        if ($index !== false) {
            array_splice($bookmarks, $index, 1);
            $this->setBookmarks($bookmarks);
        }
    }

    protected function hasBookmark($aBookmark) {
        return in_array($aBookmark, $this->getBookmarks());
    }
    
    protected function getTitleForBookmark($aBookmark) {
        //if (!$this->feeds)  // TODO drop
        //    $this->feeds = $this->loadFeedData();

        parse_str($aBookmark, $params);
        $titles = array($params['title']);
        if (isset($params['subtitle'])) {
            $titles[] = $params['subtitle'];
        }
        return $titles;
        
    }
    
    private function bookmarkType($aBookmark) {
        //parse_str($aBookmark, $params);  // TODO drop
        return 'video';
    }
    
    private function generateBookmarkLink() {
        $hasBookmarks = count($this->getBookmarks()) > 0;
        if ($hasBookmarks) {
            $bookmarkLink = array(array(
                'title' => 'Bookmarked Locations',
                'url' => $this->buildBreadcrumbURL('bookmarks', $this->args, true),
                ));
            $this->assign('bookmarkLink', $bookmarkLink);
        }
        $this->assign('hasBookmarks', $hasBookmarks);
    }  
  
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    }
    
    protected function getSections() {
         $sections = array();
         foreach ($this->feeds as $index => $feedData) {
              $sections[] = array(
                'value'    => $index,
                'title'    => $feedData['TITLE']
              );
         }
         
         return $sections;
    }
    
    protected function getListItemForVideo(VideoObject $video, $section) {
        // FIXME proper fix is either determine if desktop or adjust in javascript 
        $desc = $video->getDescription();
        if (strlen($video->getTitle())>30) {             	
            if (strlen($desc)) {
                $desc = substr($desc,0,30) . "...";
            }
        }
        
        if (strlen($desc)>75) {
            $desc = substr($desc,0,75) . "...";
        }

        return array(
            'title'=>$video->getTitle(),
            'subtitle'=> "(" . $this->getDuration($video->getDuration()) . ") " . $desc,
            'imgWidth'=>120,  
            'imgHeight'=>100,  
            'img'=>$video->getImage(),
            'url'=>$this->buildBreadcrumbURL('detail', array(
                'section'=>$section,
                'videoid'=>$video->getID()
            )));
    }

    protected function getDuration($prop_length) {
        if (!$prop_length) {
            return "";
        } elseif ($prop_length<60) {
            return "0:". $prop_length;
        } else {
            $mins = intval($prop_length / 60);
            $secs = $prop_length % 60;
            if($secs<10) {
				return $mins . ":0" . $secs;
			} else { 
				return $mins . ":" . $secs;
        	}
		}
    }
    
    protected function initializeForPage() {
   
        if ($this->pagetype=='basic') {
            $this->assign('showUnsupported', true);
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
        $this->assign('sections'      , $this->getSections());
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
          
                $this->assign('start',       $start);
                $this->assign('previousURL', $previousURL);
                $this->assign('nextURL',     $nextURL);
                $this->assign('hiddenArgs',  $hiddenArgs);
                 
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
 }