<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('DataModel');
class SocialDataModel extends ItemListDataModel
{
    protected $RETRIEVER_INTERFACE = 'SocialDataRetriever';
    protected $startDate;
    protected $endDate;
    protected $author;

    public static function getSocialDataRetrievers() {
        return array(
            'FacebookDataRetriever'=>'Facebook',
            'TwitterDataRetriever'=>'Twitter'
        );
    }

    public function getPosts() {
    	//the retriever is expected to limit the results
        return $this->retriever->getPosts();
    }
    
    public function setStartDate(DateTime $time) {
        $this->startDate = $time;
        $this->setOption('startDate', $time);
    }
    
    public function setEndDate(DateTime $time) {
        $this->endDate = $time;
        $this->setOption('endDate', $time);
    }

    public function setAuthor($author) {
        $this->author = $author;
        $this->setOption('author', $author);
    }
    
    protected function init($args) {
        parent::init($args);
    }
    
    public function search($q, $start=0, $limit=null) {
        Debug::Die_here(func_get_Args());
    }
    
    public function getUser($userID) {
        return $this->retriever->getUser($userID);
    }
    
    /*

    public function canRetrieve() {
        return $this->retriever->canRetrieve();
    }
        
    public function canPost() {
        return $this->retriever->canPost();
    }    

    public function auth(array $options) {
        return $this->retriever->auth($options);
    }    
    */
}

