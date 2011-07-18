<?php

/* 
 * Class to abstract video data
 */
class VideoObject implements KurogoObject
{
    protected $type;
    protected $id;
    protected $title;
    protected $description;
    protected $author;
    protected $published;
    protected $url;
    protected $image;
    protected $width;
    protected $height;
    protected $duration;
    protected $tags;
    protected $mobileURL;
    protected $streamingURL;
    protected $stillFrameImage;
    
    public function getType() {
        return $this->type;
    }
    
    public function setID($id) {
        $this->id = $id;
    }

    public function getID() {
        return $this->id;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function getDescription() {
        return $this->description;
    }

    public function setPublished(DateTime $published) {
        $this->published = $published;
    }

    public function getPublished() {
        return $this->published;
    }

    public function setURL($url) {
        $this->url = $url;
    }
    
    public function getURL() {
        return $this->url;
    }

    public function setMobileURL($url) {
        $this->mobileURL = $url;
    }
    
    public function getMobileURL() {
        return $this->mobileURL;
    }

    public function setStreamingURL($url) {
        $this->streamingURL = $url;
    }

    public function getStreamingURL() {
        return $this->streamingURL;
    }

    public function setImage($image) {
        $this->image = $image;
    }
    
    public function getImage() {
        return $this->image;
    }

    public function setWidth($width) {
        $this->width = $width;
    }
    
    public function getWidth() {
        return $this->width;
    }

    public function setHeight($height) {
        $this->height = $height;
    }
    
    public function getHeight() {
        return $this->height;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }
    
    public function getDuration() {
        return $this->duration;
    }

    public function setTags($tags) {
        $this->tags = $tags;
    }
    
    public function getTags() {
        return $this->tags;
    }
    
    public function setStillFrameImage($imageURL) {
        $this->stillFrameImage = $imageURL;
    }
        
    public function getStillFrameImage() {
        return $this->stillFrameImage;
    }

    /* subclasses should return true or false based on pagetype, platform */    
    public function canPlay(DeviceClassifier $deviceClassifier) {
        return true;
    }
}
