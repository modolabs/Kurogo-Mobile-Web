<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class EmergencyNotice
{
    protected $pubDate;
    protected $title;
    protected $description;
    protected $content;
    protected $link;

    public function getPubDate()
    {
        return $this->pubDate;
    }
    
    public function getPubTimestamp() {
        if ($this->pubDate) {
            return $this->pubDate->format('U');
        }
    }

    public function getTitle() {
        return $this->title;
    }
    
    public function getDescription() {
        return $this->description;
    }

    public function getLink() {
        return $this->link;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getContent() { 
        return $this->content; 
    }

    public function setPubDate(DateTime $pubDate) {
        $this->pubDate = $pubDate;
    }

    public function setLink($link) {
        $this->link = $link;
    }
}
