<?php
/**
 * PhotosWebModule 
 * 
 * @uses WebModule
 * @package 
 * @version $id$
 * @copyright 2011
 * @author Jeffery You <jianfeng.you@symbio.com> 
 */

includePackage('Photos');

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
            	$photos = array();
            	foreach($this->feeds as $feed){
                    $controller = $this->getFeed($feed['INDEX']);
                    if ($defaultPhoto = $controller->getDefaultPhoto()) {
                        $photo['title'] = $controller->getTitle();
                        $photo['type'] = $defaultPhoto->getType();
                        $photo['albumcount'] = $this->getLocalizedString('PHOTOS_ALBUMCOUNT',$controller->getAlbumSize());
                        // use base64_encode to make sure it will not be blocked by GFW
                        $photo['url'] = $this->buildBreadcrumbURL('album', array('id' => $feed['INDEX']), true);
                        $photo['img'] = $defaultPhoto->getThumbnailUrl();
                        $photos[] = $photo;
                    }
                }
                $this->assign('photos', $photos);
                $this->assign('description', $this->getModuleVar('description','strings'));
                $this->assign('sections', $this->getSectionsFromFeeds($this->feeds));
                break;
                
        	case 'album':
        		$album = $this->getArg('id', $this->getDefaultSection());
        		$controller = $this->getFeed($album);
        		$this->setPageTitles($controller->getTitle());

                $maxPerPage = $this->getOptionalModuleVar('MAX_RESULTS', 20);
                $start = $this->getArg('start', 0);

                $controller->setStart($start);
                $controller->setLimit($maxPerPage);
        		$items = $controller->getPhotos();
        		$totalItems = $controller->getTotalItems();
        		
        		$photos = array();
        		foreach($items as $item){
        			$photo['title'] = $item->getTitle();
        			$photo['url'] = $this->buildBreadcrumbURL('show', array('id' => base64_encode($item->getID()), 'album' => $album), true);
                    $photo['img'] = $item->getThumbnailUrl();
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
                $id = base64_decode($this->getArg('id'));
                $photo = $controller->getPhoto($id);
                $this->setPageTitles($photo->getTitle());
                $this->assign('photoURL',    $photo->getUrl());
                $this->assign('photoTitle',  $photo->getTitle());
                $this->assign('photoAuthor', $photo->getAuthor());
                $this->assign('photoDate',   $this->timeText($photo));
                
                break;
        }
    }
}
