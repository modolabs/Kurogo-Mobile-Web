<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class SocialShellModule extends ShellModule {

    protected $id = 'social';
    protected $feeds = array();
    
    public function getAllControllers() {
        $controllers = array();
        
        $feeds = $this->loadFeedData();
        
        foreach ($feeds as $feed=>$feedData) {
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : 'SocialDataModel';
            $controllers[$feed] = SocialDataModel::factory($modelClass, $feedData);
        }
        
        return $controllers;
    }
    
    protected function initializeForCommand() {
        
        switch($this->command) {
            case 'fetchAllData':
                $this->authors = array();
                $this->preFetchAllData();
                
                return 0;
                
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }

    protected function preFetchData(DataModel $controller, &$response) {
    	$retriever = $controller->getRetriever();
        $posts = $retriever->getPosts();
        $response = $retriever->getLastResponse();
        foreach ($posts as $key => $post) {
            if (is_object($post) && ($author = $post->getAuthor()) && !in_array($author, $this->authors)) {
                $this->authors[] = $author;
                $controller->getUser($author);
            }
        }
    }
}