<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Video');

class VideoWebModule extends WebModule
{
    protected static $defaultModel = 'VideoDataModel';
    protected $id='video'; 
    protected $feeds = array();
        
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    }
    
    protected function getDefaultSection() {
        return key($this->feeds);
    }

    public function linkForItem(KurogoObject $video, $data=null) {
    
        $options = array(
            'id'=>$video->getID()
        );
        
        foreach (array('feed','filter') as $field) {
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
            'img'=>$video->getImage(),
            'large'=>true,
        );
    }
    
    public function searchItems($searchTerms, $limit=null, $options=null) {
        
        $section = isset($options['feed']) ? $options['feed'] : $this->getDefaultSection();
        $controller = $this->getFeed($section);
                
        $controller->setLimit($limit);
        $items = $controller->search($searchTerms);

        return $items;
    }
    
    protected function getFeed($feed=null) {
        $feed = isset($this->feeds[$feed]) ? $feed : $this->getDefaultSection();
        $feedData = $this->feeds[$feed];

        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
        $controller = VideoDataModel::factory($modelClass, $feedData);

        return $controller;
    }
    
    protected function initializeForPage() {
   
        if ($this->pagetype == 'basic') {
            $this->setTemplatePage('videoError.tpl');
            return;
        }
        
        if (count($this->feeds) == 0) {
            throw new KurogoConfigurationException("No video feeds configured");
        }
    
        // Categories / Sections
        
        $section = $this->getArg(array('feed', 'section'), $this->getDefaultSection());
        if (!isset($this->feeds[$section])) {
            $section = $this->getDefaultSection();
        }
        
        $this->assign('currentSection', $section);
        $this->assign('sections'      , VideoModuleUtils::getSectionsFromFeeds($this->feeds));
        
        $controller = $this->getFeed($section);
        
        switch ($this->page) {
            case 'pane':
                if ($this->ajaxContentLoad) {
                  $start = 0;
                  $maxPerPage = $this->getOptionalModuleVar('MAX_PANE_RESULTS', 5);
                  $data = array(
                      'noBreadcrumbs'=>true,
                      'feed'=>$section
                  );
  
                  $controller->setStart($start);
                  $controller->setLimit($maxPerPage);
                  $items = $controller->items();
                  $videos = array();
  
                  foreach ($items as $video) {
                      $videoLink = $this->linkForItem($video, $data);
                      
                      $videoLink['url'] = $this->buildURL('index').
                          '#'.urlencode(FULL_URL_PREFIX.ltrim($videoLink['url'], '/'));
                      $videoLink['img'] = $video->getStillFrameImage();
                      unset($videoLink['imgWidth']);
                      unset($videoLink['imgHeight']);
                      
                      $videos[] = $videoLink;
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
                $controller->setStart($start);
                $controller->setLimit($maxPerPage);
                
                if ($this->page == 'search') {
                    if ($filter = $this->getArg('filter')) {
                        $searchTerms = trim($filter);
                        $this->setLogData($searchTerms);
                        $items = $controller->search($searchTerms);
                        $this->assign('searchTerms', $searchTerms);
                    } else {
                        $this->redirectTo('index', array('feed'=>$section), false);
                    }
                } else {
                    $this->setLogData($section, $controller->getTitle());
                    $items = $controller->items();
                }
                             
                $totalItems = $controller->getTotalItems();
                $videos = array();
                foreach ($items as $video) {
                    $videos[] = $this->linkForItem($video, array('feed'=>$section));
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
                if ($this->page == 'search') {
                    $hiddenArgs = array(
                      'feed'=>$section
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
                    if (isset($params['feed'], $this->feeds[$params['feed']], $params['id'])) {
                        if (!isset($controllerCache[$params['feed']])) {
                            $controllerCache[$params['feed']] = $this->getFeed($params['feed']);
                        }
                        
                        if ($video = $controllerCache[$params['feed']]->getItem($params['id'])) {
                            $videos[] = $this->linkForItem($video, $params);
                        }
                    }
                }
                $this->assign('videos', $videos);
                
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupVideosListing();');
                break;
            
            case 'player':
                $videoid = $this->getArg(array('id','videoid'));
                if ($video = $controller->getItem($videoid)) {
                    $this->assign('videoObject',      $video);
                } 
                break;
            case 'detail':
                $videoid = $this->getArg(array('id', 'videoid'));
            
                if ($video = $controller->getItem($videoid)) {
                    $this->setLogData($videoid, $video->getTitle());
                    
                    $this->assign('videoTitle',       $video->getTitle());
                    $this->assign('videoDescription', $video->getDescription());
                    $this->assign('videoAuthor'     , $video->getAuthor());
                    $this->assign('videoDate'       , $video->getPublished()->format('M j, Y'));
                    $this->assign('videoObject',      $video);
                    
                    $body = $video->getDescription() . "\n\n" . $video->getURL();
                    
                    if ($this->getOptionalModuleVar('SHARING_ENABLED', 1)) {
                        $this->assign('shareEmailURL', $this->buildMailToLink("", $video->getTitle(), $body));
                        $this->assign('shareTitle',    $this->getLocalizedString('VIDEO_SHARE_MESSAGE'));
                        $this->assign('shareURL',      $video->getURL());
                        $this->assign('shareRemark',   $video->getTitle());
                    }
    
                      // Bookmark
                    if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                        $cookieParams = array(
                          'feed'  => $section,
                          'id'    => $videoid
                        );
                        
                        $cookieID = http_build_query($cookieParams);
                        $this->generateBookmarkOptions($cookieID);
                    }
                } else {
                    $this->redirectTo('index', array('feed'=>$section),false);
                }
                break;
        }
    }
    
 }
