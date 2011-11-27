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
            throw new KurogoConfigurationException("No photo feeds configured");
        }

        switch($this->page) {
            case 'index':
            	$photos = array();
            	foreach($this->feeds as $feed){
                    $controller = $this->getFeed($feed['INDEX']);
                    $defaultPhoto = $controller->getDefaultPhoto();

                    $photo['title'] = $controller->getTitle();
                    $photo['type'] = $defaultPhoto->getType();
                    $photo['albumcount'] = $controller->getAlbumSize() . ' photos';
                    // use base64_encode to make sure it will not be blocked by GFW
                    $photo['url'] = $this->buildBreadcrumbURL('album', array('id' => $feed['INDEX']), true);
                    $photo['img'] = $defaultPhoto->getThumbnailUrl();
                    $photos[] = $photo;
                }
                $this->assign('photos', $photos);
                $this->assign('sections', $this->getSectionsFromFeeds($this->feeds));
                break;
        	case 'album':
        		$album = $this->getArg('id', $this->getDefaultSection());
        		$controller = $this->getFeed($album);
        		$pageTitle = $controller->getTitle();
        		$this->setBreadcrumbTitle($pageTitle);
        		$this->setPageTitle($pageTitle);

        		
        		// make this changeable via url?
			    $limit = 4;
        		$page = $this->getArg('page', 0);
        		if($page < 0){
        		    $page = 0;
        		}

                $start = $limit * $page;
        		$items = $controller->getPhotosByIndex($start, $limit);
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
        		
        		$this->assign('fullTitle', $pageTitle);
        		$this->assign('springboardID', 'photoSpringboard');
        		$this->assign('page', $page + 1);
        		$this->assign('totalPages', ceil($totalItems/$limit));

        		if((($page * $limit) + $limit) < $totalItems){
        			$this->assign('next', 'Next');
        			$this->assign('nextURL', $this->buildBreadcrumbURL('album', array('id' => $this->getArg('id'), 'page' => $page+1), false));
        		}
        		if($page > 0){
        			$this->assign('prev', 'Previous');
        			$this->assign('prevURL', $this->buildBreadcrumbURL('album', array('id' => $this->getArg('id'), 'page' => $page-1), false));
        		}
        		break;
            case 'show':
            	$album = $this->getArg('album', null);
            	if(!isset($album)){
            		throw new KurogoUserException("Invalid album specified");
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
