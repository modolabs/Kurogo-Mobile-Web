<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * PhotosWebModule 
 * 
 * @uses WebModule
 * @package 
 */

class PhotosWebModule extends WebModule {
    protected static $defaultModel = 'PhotosDataModel';
    protected $id = 'photos'; 
    protected $feeds = array();
    protected $feedIndex = 0;
    
    protected $showAuthor = 1;
    protected $showDate = 1;
    protected $showDescription = 1;
        
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
        
        if (count($this->feeds)==0) {
            return;
        }
        
        $this->feedIndex = $this->getArg(array('feed', 'album'), 0);
        if (!isset($this->feeds[$this->feedIndex])) {
            $this->feedIndex = $this->getDefaultSection();
        }

        $feedData = $this->feeds[$this->feedIndex];
        $this->showDescription = isset($feedData['SHOW_DESCRIPTION']) ? $feedData['SHOW_DESCRIPTION'] : true;
        $this->showAuthor = isset($feedData['SHOW_AUTHOR']) ? $feedData['SHOW_AUTHOR'] : true;
        $this->showDate = isset($feedData['SHOW_DATE']) ? $feedData['SHOW_DATE'] : true;
    } 

    protected function getDefaultSection() {
        return key($this->feeds);
    }

    protected function getFeed($feed = null) {
        $feed = isset($this->feeds[$feed]) ? $feed : $this->getDefaultSection();
        $feedData = $this->feeds[$feed];

        $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
        $controller = DataModel::factory($modelClass, $feedData);

        $maxResultsOption = $this->pagetype == 'tablet' ? 'MAX_TABLET_RESULTS' : 'MAX_RESULTS';
        $maxPerPage = $this->getOptionalModuleVar($maxResultsOption, 20);
        $controller->setLimit($maxPerPage);

        return $controller;
    }

    private function getSectionsFromFeeds($feeds) {
        $sections = array();
        foreach ($feeds as $index => $feedData) {
            $sections[] = array(
                'value' => $index,
                'title' => $feedData['TITLE']
            );
        }         
        return $sections;
    }

  protected function timeText($photo, $timeOnly=false) {
    includePackage('Calendar');
        return DateFormatter::formatDate($photo->getPublished(), DateFormatter::SHORT_STYLE, DateFormatter::SHORT_STYLE);
  }

    protected function initializeForPage() {
        /**
         * no feed, throw exception
         */
        if (count($this->feeds) == 0) {
            throw new KurogoConfigurationException($this->getLocalizedString('PHOTOS_FEED_CONFIGURE_ERROR_MESSAGE'));
        }

        switch($this->page) {
            case 'index':
                // if there is only one album, go straight to album
                if(count($this->feeds) == 1) {
                    $albumId = key($this->feeds);
                    $this->redirectTo('album', array('id' => $albumId), true);
                    exit;
                }
            	$albums = array();
            	foreach($this->feeds as $feed => $feedData){
                    $controller = $this->getFeed($feed); 
                    if ($defaultPhoto = $controller->getDefaultPhoto()) {
                        $album['title'] = $controller->getTitle();
                        $album['type'] = $defaultPhoto->getType();
                        $album['albumcount'] = $this->getLocalizedString('PHOTOS_ALBUMCOUNT',$controller->getAlbumSize());
                        $album['url'] = $this->buildBreadcrumbURL('album', array('feed' => $feed), true);
                        $album['img'] = $defaultPhoto->getThumbnailUrl($this->pagetype);
                        $albums[] = $album;
                    }
                }
                $this->assign('albums', $albums);
                $this->assign('description', $this->getOptionalModuleVar('description','','strings'));
                $this->assign('sections', $this->getSectionsFromFeeds($this->feeds));
                break;
                
        	case 'album':
        		$album = $this->getArg(array('feed','id'), $this->getDefaultSection());
        		$controller = $this->getFeed($album);
                if (count($this->feeds) > 1) {
                    $this->setPageTitles($controller->getTitle());
                }
                
                $maxPerPage = $controller->getLimit();
                $start = $this->getArg('start', 0);

                $controller->setStart($start);
        		$items = $controller->getPhotos();
        		$totalItems = $controller->getTotalItems();
        		
        		$photos = array();
                for ($i=0; $i < count($items); $i++) {
                    $item = $items[$i];
                    $photo['title'] = $item->getTitle();
                    $index = $start + $i;
                    $photo['url'] = $this->buildBreadcrumbURL('show', array('id' => $index, 'feed' => $album), true);
                    $photo['img'] = $item->getThumbnailUrl($this->pagetype);
                    $photos[] = $photo;
                }
        		
        		$this->assign('photos', $photos);
        		$this->assign('albumcount', $totalItems);
        		
                if ($totalItems > $maxPerPage) {
                    $args = $this->args;
                 
                    if ($start > 0) {
                        $args['start'] = $start - $maxPerPage;
                        $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
                        $this->assign('prev', $this->getLocalizedString('PREVIOUS_TEXT'));
                        $this->assign('prevURL', $previousURL);
                    }
                    
                    if (($totalItems - $start) > $maxPerPage) {
                        $args['start'] = $start + $maxPerPage;
                        $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
                        $this->assign('next', $this->getLocalizedString('NEXT_TEXT'));
                        $this->assign('nextURL',     $nextURL);
                    }		
                }

        		break;
          case 'show':

            $album = $this->getArg(array('feed', 'album'), null);
            if(!isset($album)){
              throw new KurogoUserException($this->getLocalizedString('PHOTOS_SPECIFIED_ERROR_MESSAGE'));
            }
            $controller = $this->getFeed($album);  	  
            $id = $this->getArg('id');
            if (!$photo = $controller->getPhoto($id)) {
                throw new KurogoUserException($this->getLocalizedString('PHOTO_NOT_FOUND'));
            }
            $preAndNextId = $controller->getPrevAndNextID($id);
            
            if($preAndNextId['prev'] !== false){
              $this->assign('prevURL', $this->buildBreadcrumbURL('show', array('id' => $preAndNextId['prev'], 'feed' => $album), false));
            }
            
            if($preAndNextId['next'] !== false){
              $this->assign('nextURL', $this->buildBreadcrumbURL('show', array('id' => $preAndNextId['next'], 'feed' => $album), false));
            }
            
            if ($this->pagetype == 'tablet') {
                $this->addInternalJavascript('/common/javascript/lib/ellipsizer.js');
                $this->addOnLoad('setupShowPageEllipsizer();');
            }
            
            $this->assign('photoURL',    $photo->getUrl());
            $this->assign('prev', $this->getLocalizedString('PREVIOUS_TEXT'));
            $this->assign('next', $this->getLocalizedString('NEXT_TEXT'));
            $this->assign('showDescription', $this->showDescription);
            $this->assign('showAuthor', $this->showAuthor);
            $this->assign('showDate', $this->showDate);
            $this->assign('photoDescription', $photo->getDescription());
            $this->assign('photoTitle',  $photo->getTitle());
            $this->assign('photoAuthor', $photo->getAuthor());
            $this->assign('photoDate',   $this->timeText($photo));
            
            break;
        }
    }
}
