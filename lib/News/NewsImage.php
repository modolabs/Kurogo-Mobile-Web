<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class NewsImage implements KurogoImage {
    protected $url;
    protected $width;
    protected $height;
    protected $thumbnail = false;
    protected $imageOptions = array();
    protected $thumbnailOptions = array();
    protected $setImageLoaderURL = false;

    public function init($args) {
        // set image resize/crop parameters
        
        if (isset($args['THUMB_MAX_WIDTH'])) {
            $this->thumbnailOptions['max_width'] = intval($args['THUMB_MAX_WIDTH']);
        }
        if (isset($args['THUMB_MAX_HEIGHT'])) {
            $this->thumbnailOptions['max_height'] = intval($args['THUMB_MAX_HEIGHT']);
        }
        if (isset($args['THUMB_CROP'])) {
            $this->thumbnailOptions['crop'] = (boolean)$args['THUMB_CROP'];
        }
        if (isset($args['THUMB_BACKGROUND_RGB'])) {
            $this->thumbnailOptions['rgb'] = strval($args['THUMB_BACKGROUND_RGB']);
        }
    }
    
    public function setThumbnail($thumbnail) {
        $this->thumbnail = $thumbnail;
    }
    
    public function isThumbnail() {
        return $this->thumbnail;
    }

    /**
     * Get title.
     *
     * @return title.
     */
    public function getTitle() {
        return $this->title;
    }
    
    /**
     * Set title.
     *
     * @param title the value to set.
     */
    public function setTitle($title) {
        $this->title = $title;
    }
    
    /**
     * Get url.
     *
     * @return url.
     */
    public function getUrl() {
        if (!$this->setImageLoaderURL) {
            $this->url = ImageLoader::cacheImage($this->url, $this->isThumbnail() ? $this->thumbnailOptions : $this->imageOptions);
            $this->setImageLoaderURL = true;
        }
        return $this->url;
    }
    
    /**
     * Set url.
     *
     * @param url the value to set.
     */
    public function setUrl($url) {
        $this->url = $url;
    }
    
    /**
     * Get width.
     *
     * @return width.
     */
    public function getWidth() {
        return $this->width;
    }
    
    /**
     * Set width.
     *
     * @param width the value to set.
     */
    public function setWidth($width) {
        $this->width = $width;
    }
    
    /**
     * Get height.
     *
     * @return height.
     */
    public function getHeight() {
        return $this->height;
    }
    
    /**
     * Set height.
     *
     * @param height the value to set.
     */
    public function setHeight($height) {
        $this->height = $height;
    }
}
