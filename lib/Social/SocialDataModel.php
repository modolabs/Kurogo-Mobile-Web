<?php

includePackage('DataModel');
class SocialDataModel extends ItemListDataModel
{
    protected $RETRIEVER_INTERFACE = 'SocialDataRetriever';
    protected $startDate;
    protected $endDate;
    protected $author;

    public function getPosts() {
        $this->setOption('action','posts');
        return $this->items();
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
        $response = $this->retriever->getUser($userID);
        return $this->parseResponse($response);
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

