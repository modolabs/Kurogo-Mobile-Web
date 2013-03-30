<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/* 
 * Class to abstract video data
 */
class VideoObject extends KurogoDataObject
{
    protected $type;
    protected $author;
    protected $published;
    protected $url;
    protected $image;
    protected $width;
    protected $height;
    protected $aspect;
    protected $duration;
    protected $tags;
    protected $mobileURL;
    protected $streamingURL;
    protected $stillFrameImage;
    
    // Note: browsers with javascript will reset the width to 100% and
    // maintain the aspect ratio.  So the widths below are only used as an
    // initial value and when javascript is unavailable.
    protected $DEFAULT_COMPLIANT_VIDEO_WIDTH = 300;
    protected $DEFAULT_TABLET_VIDEO_WIDTH = 600;
    protected $DEFAULT_VIDEO_ASPECT_RATIO = 1.6; // 16x10
    
    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        return $this->type = $type;
    }
    
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search': //case insensitive
                    return  (stripos($this->getTitle(), $value)!==FALSE) ||
                            (stripos($this->getDescription(), $value)!==FALSE);
                    break;
            }
        }   
        
        return true;     
    }
    
    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
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

    public function setAspectRatio($aspect) {
        $this->aspect = floatval($aspect);
    }
    
    public function getAspectRatio() {
        if ($this->aspect) {
            return $this->aspect;
        }
        $width = intval($this->getWidth());
        $height = intval($this->getHeight());
        if ($width && $height) {
            return ($width / $height);
        } else {
            return $this->DEFAULT_VIDEO_ASPECT_RATIO;
        }
    }
    
    public function getDeviceWidth() {
        $width = 200;
        switch (Kurogo::deviceClassifier()->getPagetype()) {
            case 'compliant':
            case 'basic':
                $width = $this->DEFAULT_COMPLIANT_VIDEO_WIDTH;
                break;
                
            case 'tablet':
            default:
                $width = $this->DEFAULT_TABLET_VIDEO_WIDTH;
                break;
        }
        return $width;
    }

    public function getDeviceHeight() {
        return ceil($this->getDeviceWidth() / $this->getAspectRatio());
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
    
    /* subclasses should return play url based on pagetype, platform */ 
    public function getPlayerURL() {
        return '';
    }
}
