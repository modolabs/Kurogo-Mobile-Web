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
                    // use base64_encode to make sure it will not be blocked by GFW
                    $photo['url'] = $this->buildBreadcrumbURL('album', array('id' => $feed['INDEX']), true);
                    $photo['img'] = $defaultPhoto->getTUrl();
                    $photos[] = $photo;
                }
                $this->assign('photos', $photos);
                $this->assign('sections', $this->getSectionsFromFeeds($this->feeds));
                break;
        	case 'album':
        		$album = $this->getArg('id', $this->getDefaultSection());
        		$controller = $this->getFeed($album);
        		$items = $controller->items();

        		$photos = array();
        		foreach($items as $item){
        			$photo['title'] = $item->getTitle();
        			$photo['url'] = $this->buildBreadcrumbURL('show', array('id' => base64_encode($item->getID()), 'album' => $album), true);
                    $photo['img'] = $item->getTUrl();
                    $photos[] = $photo;
        		}
        		$this->assign('photos', $photos);
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
                $this->assign('photo', $photo);
                break;
        }
    }
}
