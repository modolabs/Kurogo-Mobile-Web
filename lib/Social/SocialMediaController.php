<?php

abstract class SocialMediaController extends DataController
{
    protected $startDate;
    protected $endDate;
    protected $author;
    
    abstract public function search($q, $start=0, $limit=null);
    abstract public function getUser($userID);
    abstract public function canRetrieve();
    abstract public function canPost();
    abstract public function auth(array $options);

    public function setStartDate(DateTime $time) {
        $this->startDate = $time;
    }
    
    public function setEndDate(DateTime $time) {
        $this->endDate = $time;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    protected function init($args) {
        parent::init($args);
    }
    
}

abstract class SocialMediaUser
{
    protected $userID;
    protected $name;
    protected $email;
    protected $dataController;
    protected $imageURL;
    protected $image;

    public function __construct(SocialMediaController $dataController) {
        $this->dataController = $dataController;
    }
    
    public function setUserID($userID) {
        $this->userID = $userID;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setImage($image) {
        $this->image = $image;
    }

    public function getImage() {
        return $this->image;
    }

    public function setImageURL($imageURL) {
        $this->imageURL = $imageURL;
    }

    public function getImageURL() {
        return $this->imageURL;
    }
}

abstract class SocialMediaPost implements KurogoObject
{
    protected $id;
    protected $author;
    protected $url;
    protected $body;
    protected $created;
    protected $parent_id;
    protected $thread_id;
    protected $likes=0;
    protected $replyCount=0;
    protected $replies=array();
    protected $links=array();
    protected $images=array();
    protected $dataController;

    abstract public function getReplyURL();
    abstract public function getLikeURL();
    abstract public function getAuthorUser();

    public function __construct(SocialMediaController $dataController) {
        $this->dataController = $dataController;
    }
    
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search': //case insensitive
                    return  (stripos($this->getBody(), $value)!==FALSE);
                    break;
            }
        }   
        
        return true;     
    }
    
    public function getID() {
        return $this->id;
    }

    public function setID($id) {
        $this->id = $id;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getURL() {
        return $this->url;
    }

    public function setURL($url) {
        $this->url = $url;
    }
    
    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function getCreated() {
        return $this->created;
    }

    public function setCreated(DateTime $created) {
        $this->created = $created;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function setParentID($parent_id) {
        $this->parent_id= $parent_id;
    }

    public function getThreadID() {
        return $this->thread_id;
    }

    public function setThreadID($thread_id) {
        $this->thread_id= $thread_id;
    }
    
    public function getLikeCount() {
        return $this->likes;
    }

    public function setLikeCount($likes) {
        $this->likes = $likes;
    }

    public function getReplyCount() {
        return $this->replyCount;
    }

    public function setReplyCount($replyCount) {
        $this->replyCount = $replyCount;
    }

    public function getReplies() {
        return $this->replies;
    }

    public function addReply(SocialMediaPost $reply) {
        $this->replies[] = $reply;
        $this->setReplyCount(count($this->replies));
    }

    public function addLink($link) {
        $this->links[] = $link;
    }

    public function getLinks() {
        return $this->links;
    }

    public function addImage($image) {
        $this->images[] = $image;
    }

    public function getImages() {
        return $this->images;
    }
    
}