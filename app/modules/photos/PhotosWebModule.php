<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
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
        
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
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
            	foreach($this->feeds as $feed){
                    $controller = $this->getFeed($feed['INDEX']);
                    if ($defaultPhoto = $controller->getDefaultPhoto()) {
                        $album['title'] = $controller->getTitle();
                        $album['type'] = $defaultPhoto->getType();
                        $album['albumcount'] = $this->getLocalizedString('PHOTOS_ALBUMCOUNT',$controller->getAlbumSize());
                        $album['url'] = $this->buildBreadcrumbURL('album', array('id' => $feed['INDEX']), true);
                        $album['img'] = $defaultPhoto->getThumbnailUrl($this->pagetype);
                        $albums[] = $album;
                    }
                }
                $this->assign('albums', $albums);
                $this->assign('description', $this->getModuleVar('description','strings'));
                $this->assign('sections', $this->getSectionsFromFeeds($this->feeds));
                break;
                
        	case 'album':
        		$album = $this->getArg('id', $this->getDefaultSection());
        		$controller = $this->getFeed($album);
        		$this->setPageTitles($controller->getTitle());

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
                    $photo['url'] = $this->buildBreadcrumbURL('show', array('id' => $index, 'album' => $album), true);
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
            	$album = $this->getArg('album', null);
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
                	$this->assign('prevURL', $this->buildBreadcrumbURL('show', array('id' => $preAndNextId['prev'], 'album' => $album), false));
                }
                
                if($preAndNextId['next'] !== false){
                	$this->assign('nextURL', $this->buildBreadcrumbURL('show', array('id' => $preAndNextId['next'], 'album' => $album), false));
                }
                
                $this->assign('photoURL',    $photo->getUrl());
                $this->assign('prev', $this->getLocalizedString('PREVIOUS_TEXT'));
                $this->assign('next', $this->getLocalizedString('NEXT_TEXT'));
                $this->assign('photoTitle',  $photo->getTitle());
                $this->assign('photoAuthor', $photo->getAuthor());
                $this->assign('photoDate',   $this->timeText($photo));
                
                break;
        }
    }
}
