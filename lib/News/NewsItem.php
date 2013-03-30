<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class NewsItem extends KurogoDataObject implements NewsItemInterface {

    protected $author;
    protected $link;
    protected $content;
    protected $pubDate;
    protected $category = array();
    protected $thumbnail;
    protected $image;
    protected $fetchContent = true;
    protected $initArgs;

    public function setFetchContent($bool) {
        $this->fetchContent =  $bool ? true : false;
    }

    public function init($args) {
        $this->initArgs = $args;
        if (isset($args['FETCH_CONTENT'])) {
            $this->setFetchContent($args['FETCH_CONTENT']);
        }
    }

    public function getGUID() {
        return $this->getID();
    }
    
    public function getID() {
    	if ($this->id) {
			return $this->id;
		} elseif ($this->link) {
			return $this->link;
		}
    }

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter) {
                case 'search':
                    return  (stripos($this->getTitle(), $value)!==FALSE) ||
                        (stripos($this->getDescription(), $value)!==FALSE) ||
                        (stripos($this->getContent(false),     $value)!==FALSE);
                    break;
            }
        }

        return true;
    }

    /**
     * Get author.
     *
     * @return author.
     */
    public function getAuthor() {
        return $this->author;
    }

    /**
     * Set author.
     *
     * @param author the value to set.
     */
    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getImage() {
        return $this->image;
    }

    public function getThumbnail() {
        return $this->thumbnail;
    }
    
    public function setMediaGroup($group) {
        if ($thumbnail = Kurogo::arrayVal($group, 'thumbnail')) {
            $this->setThumbnail($thumbnail);
        }

        if ($image = Kurogo::arrayVal($group, 'image')) {
            $this->setImage($image);
        }
    }

    public function setThumbnail($image) {
        if ($image instanceOf NewsImage) {
            $this->thumbnail = $image;
        } elseif (is_array($image)) {
            $this->setThumbnail(current($image));
        }
    }
    
    public function setImage($image) {
        if ($image instanceOf NewsImage) {
            $this->image = $image;
            if (!$this->thumbnail) {
                $thumbnail = clone $image;
                $thumbnail->setThumbnail(true);
                $this->setThumbnail($thumbnail);
            }
        } elseif (is_array($image)) {
            $this->setImage(current($image));
        }
    }
    
    /**
     * Get link.
     *
     * @return link.
     */
    public function getLink() {
        return $this->link;
    }

    /**
     * Set link.
     *
     * @param link the value to set.
     */
    public function setLink($link) {
        $this->link = $link;
    }

    /**
     * Get content.
     *
     * @return content.
     */
    public function getContent($fetch=true) {
        if (strlen($this->content)==0) {
            if ($this->fetchContent && $fetch && ($url = $this->getLink())) {
                $reader = new KurogoReader($url, $this->initArgs);
                $this->setContent($reader->getContent());
            }
        }

        return $this->content;
    }

    /**
     * Set content.
     *
     * @param content the value to set.
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * Get pubDate.
     *
     * @return pubDate.
     */
    public function getPubDate() {
        return $this->pubDate;
    }

    public function getPubTimestamp() {
        if ($this->pubDate) {
            return $this->pubDate->format('U');
        }
    }

    /**
     * Set pubDate.
     *
     * @param pubDate the value to set.
     */
    public function setPubDate(DateTime $pubDate) {
        $this->pubDate = $pubDate;
    }

    /**
     * Get category.
     *
     * @return category.
     */
    public function getCategory() {
        return $this->category;
    }
    
    /**
     * Set category.
     *
     * @param category the value to set.
     */
    public function setCategory($category) {
        $this->category = $category;
    }
}
