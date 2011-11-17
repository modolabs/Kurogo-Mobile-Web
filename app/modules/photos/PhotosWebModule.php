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
    
        $section = $this->getArg('section', $this->getDefaultSection());
        if (!isset($this->feeds[$section])) {
            $section = $this->getDefaultSection();
        }

        $this->assign('currentSection', $section);
        /**
         * get controller based on $section
         */
        $controller = $this->getFeed($section);

        switch($this->page) {
            case 'index':
                $title = $controller->getTitle();
                $this->setPageTitles($title);
                $items = $controller->getPhotos();
                $photos = array();
                foreach($items as $item) {
                    $photo = array();
                    $photo['title'] = $item->getTitle();
                    // use base64_encode to make sure it will not be blocked by GFW
                    $photo['url'] = $this->buildBreadcrumbURL('show', array('id' => base64_encode($item->getID()), 'section' => $section), true);
                    $photo['img'] = $item->getTUrl();
                    $photos[] = $photo;
                }
                $this->assign('photos', $photos);
                $this->assign('sections', $this->getSectionsFromFeeds($this->feeds));
                break;
            case 'show':
                $id = base64_decode($this->getArg('id'));
                $photo = $controller->getPhoto($id);
                $this->setPageTitles($photo->getTitle());
                $this->assign('photo', $photo);
                break;
        }
    }
}
