<?php

class PhotoObject implements KurogoObject {
    protected $id;
    protected $title;
    /**
     * url 
     * alternate photo url
     * 
     * @var mixed
     * @access protected
     */
    protected $url;
    protected $description;
    /**
     * type 
     * privider type name
     * eg: flickr, picasa
     * 
     * @var string
     * @access protected
     */
    protected $type;
    protected $mimeType;

    /**
     * t_url 
     * small/thumb image url
     * 
     * @var string
     * @access protected
     */
    protected $thumbnailUrl;

    /**
     * date_taken 
     * photo taken datetime
     * 
     * @var datetime
     * @access protected
     */
    protected $author;
    /**
     * published 
     * publish photo datetime
     * 
     * @var datetime
     * @access protected
     */
    protected $published;
    protected $width;
    protected $height;
    protected $tags;
    
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }

        return true;
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

    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
    }
    
    public function getMimeType() {
        return $this->mimeType;
    }
    
    public function getThumbnailUrl() {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl($url) {
        $this->thumbnailUrl = $url;
    }

    public function getHeight() {
        return $this->height;
    }

    public function setHeight($height) {
        $this->height = $height;
    }

    public function getWidth() {
        return $this->width;
    }

    public function setWidth($width) {
        $this->width = $width;
    }

    public function setTags($tags) {
        $this->tags = $tags;
    }
    
    public function getTags() {
        return $this->tags;
    }

    public function getType() {
        return $this->type;
    }
}
