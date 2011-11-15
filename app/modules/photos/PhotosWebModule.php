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

includePackage('Photo');

class PhotosWebModule extends WebModule {
    protected static $defaultModel = 'PhotoDataModel';
    protected static $defaultController = 'PhotoDataController';
    protected $id = 'photo'; 
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

        /**
         * get controller based on $section
         */
        $controller = $this->getFeed($section);
        $title = $controller->getTitle();
        $items = $controller->items();
        var_dump($items);
        exit;

        switch($this->page) {
        }
    }
}
