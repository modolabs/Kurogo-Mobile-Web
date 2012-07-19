<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class VideoWebModule extends WebModule
{
    protected static $defaultModel = 'VideoDataModel';
    protected static $defaultController = 'VideoDataController';
    protected $id='video'; 
    protected $feeds = array();
    protected $legacyController = false;
        
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    }
    
    protected function getDefaultSection() {
        return key($this->feeds);
    }

    public function linkForItem(KurogoObject $video, $data=null) {
    
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
                
        if ($this->legacyController) {
            $items = $controller->search($searchTerms, 0, $limit);
        } else {
            $controller->setLimit($limit);
            $items = $controller->search($searchTerms);
        }
      	return $items;
    }
    
    protected function getFeed($feed=null) {
        $feed = isset($this->feeds[$feed]) ? $feed : $this->getDefaultSection();
        $feedData = $this->feeds[$feed];

        try {
            if (isset($feedData['CONTROLLER_CLASS'])) {
                $modelClass = $feedData['CONTROLLER_CLASS'];
            } else {
                $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
            }
            
            $controller = VideoDataModel::factory($modelClass, $feedData);
        } catch (KurogoException $e) { 
            $controller = VideoDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $this->legacyController = true;
        }

        return $controller;
    }
    
    protected function initializeForPage() {
   
        if ($this->pagetype=='basic') {
            return;
        }
        
        if (count($this->feeds)==0) {
            throw new KurogoConfigurationException("No video feeds configured");
        }
    
        // Categories / Sections
        
        $section = $this->getArg('section', $this->getDefaultSection());
        if (!isset($this->feeds[$section])) {
            $section = $this->getDefaultSection();
        }
        
        $this->assign('currentSection', $section);
        $this->assign('sections'      , VideoModuleUtils::getSectionsFromFeeds($this->feeds));
        
        $controller = $this->getFeed($section);
        $this->assign('feedData', $this->feeds[$section]);
        
        switch ($this->page)
        {  
            case 'pane':
                if ($this->ajaxContentLoad) {
                  $start = 0;
                  $maxPerPage = $this->getOptionalModuleVar('MAX_PANE_RESULTS', 5);
                  $data = array(
                      'noBreadcrumbs'=>true,
                      'section'=>$section
                  );
  
                  if ($this->legacyController) {
                      $items = $controller->items($start, $maxPerPage);
                  } else {
                      $controller->setStart($start);
                      $controller->setLimit($maxPerPage);
                      $items = $controller->items();
                  }
                  $videos = array();
  
                  foreach ($items as $video) {
                      $videos[] = $this->linkForItem($video, $data);
                  }
                  
                  foreach ($videos as $i => $video) {
                      $videos[$i]['url'] = $this->buildURL('index').
                          '#'.urlencode(FULL_URL_PREFIX.ltrim($video['url'], '/'));
                  }
  
                  $this->assign('stories', $videos);
                }
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addInternalJavascript('/common/javascript/lib/paneStories.js');
                break;
            case 'search':
            case 'index':
        
                $maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 10);
                $start = $this->getArg('start', 0);
                if (!$this->legacyController) {
                    $controller->setStart($start);
                    $controller->setLimit($maxPerPage);
                }
        	    
                if ($this->page == 'search') {
                    if ($filter = $this->getArg('filter')) {
                        $searchTerms = trim($filter);
                        $this->setLogData($searchTerms);
                        if ($this->legacyController) {
                            $items = $controller->search($searchTerms, $start, $maxPerPage);
                        } else {
                            $items = $controller->search($searchTerms);
                        }
                        $this->assign('searchTerms', $searchTerms);
                    } else {
                        $this->redirectTo('index', array('section'=>$section), false);
                    }
                } else {
                    $this->setLogData($section, $controller->getTitle());
                    if ($this->legacyController) {
                        $items = $controller->items($start, $maxPerPage);
                    } else {
                        $items = $controller->items();
                    }
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
    
    			/* only assign section if it's the search page, otherwise section comes from select box */
    			if ($this->page=='search') {
					$hiddenArgs = array(
					  'section'=>$section
					);
				} else {
					$hiddenArgs = array();
				}				
          
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupVideosListing();');
          
                $this->assign('placeholder', $this->getLocalizedString('SEARCH_MODULE', $this->getModuleName()));
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
                
                $controllerCache = array(
                    $section => $controller,
                );
                $videos = array();

                foreach ($this->getBookmarks() as $aBookmark) {
                    if (!$aBookmark) { continue; }
                    
                    parse_str(stripslashes($aBookmark), $params);
                    if (isset($params['section'], $this->feeds[$params['section']], $params['videoid'])) {
                        if (!isset($controllerCache[$params['section']])) {
                            $controllerCache[$params['section']] = $this->getFeed($params['section']);
                        }
                        
                        if ($video = $controllerCache[$params['section']]->getItem($params['videoid'])) {
                            $videos[] = $this->linkForItem($video, $params);
                        }
                    }
                }
                $this->assign('videos', $videos);
                
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupVideosListing();');
                break;
                
            case 'detail':
        
                $videoid = $this->getArg('videoid');
            
                if ($video = $controller->getItem($videoid)) {
                    $this->setLogData($videoid, $video->getTitle());
                    $this->setTemplatePage('detail-' . $video->getType());
                    if ($video->canPlay(Kurogo::deviceClassifier())) {
                        $this->assign('videoTitle',       $video->getTitle());
                        $this->assign('videoid',          $video->getID());
                        $this->assign('videoStreamingURL',$video->getStreamingURL());
                        $this->assign('videoStillImage',  $video->getStillFrameImage());
                        $this->assign('videoDescription', $video->getDescription());
                        $this->assign('videoAuthor'     , $video->getAuthor());
                        $this->assign('videoDate'       , $video->getPublished()->format('M j, Y'));
                        
                        $body = $video->getDescription() . "\n\n" . $video->getURL();
    
                        if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
                            $this->assign('shareEmailURL',    $this->buildMailToLink("", $video->getTitle(), $body));
                            $this->assign('shareTitle','Share this video');
                            $this->assign('videoURL',         $video->getURL());
                            $this->assign('shareRemark',      $video->getTitle());
                        }
        
                          // Bookmark
                        if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                          $cookieParams = array(
                            'section'  => $section,
                            'videoid'  => $videoid
                          );
                          
                          $cookieID = http_build_query($cookieParams);
                          $this->generateBookmarkOptions($cookieID);
                        }
                    } else {
                        $this->setTemplatePage('videoError.tpl');
                    }
    
    
                } else {
                    $this->redirectTo('index', array('section'=>$section),false);
                }
                break;
        }
    }
    
 }
