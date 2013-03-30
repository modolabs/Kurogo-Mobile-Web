<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('News');

class RSSDataParser extends SimpleXMLDataParser
{
    protected $multiValueElements = array('item');
    protected $imageClass ='NewsImage';
    protected $itemClass  ='NewsItem';
    protected $useDescriptionForContent = false;
    protected $userFields = array();
    
    static $standardProperties = array('title', 'link', 'description','content','pubdate','category','author');
    
    protected function setUseDescriptionForContent($bool) {
    	$this->useDescriptionForContent = $bool ? true : false;
    }

    public function setItemClass($itemClass)
    {
    	if ($itemClass) {
    		if (!class_exists($itemClass)) {
    			throw new KurogoConfigurationException("Cannot load class $itemClass");
    		}
			$this->itemClass = $itemClass;
		}
    }

    public function setImageClass($imageClass)
    {
    	if ($imageClass) {
    		if (!class_exists($imageClass)) {
    			throw new KurogoConfigurationException("Cannot load class $imageClass");
    		}
			$this->imageClass = $imageClass;
		}
    }

    protected function parseChannel($channel) {
        $channelItems = Kurogo::arrayVal($channel, 'item', array());
        $items = array();
        foreach ($channelItems as $itemData) {
            if ($item = $this->parseItem($itemData)) {
                $items[] = $item;
            }
        }
        
        return $items;
    }
            
    protected function getPropertyForKey($key)
    {
        if (in_array($key, self::$standardProperties)) {
            return $key;
        }
        
        $propertyMap = array_merge(array(
            'content:encoded'=>'content',
            'guid'=>'id',
            'dc:creator'=>'author',
            'dc:date'=>'pubdate',
            'dc:subject'=>'category',
            'enclosure'=>'thumbnail',
            'media:content'=>'thumbnail',
            'media:thumbnail'=>'thumbnail',
            'media:group'=>'mediagroup',
            'body'=>'content',
        ), $this->userFields);
                        
        return Kurogo::arrayVal($propertyMap, $key);
    }
    
    protected function processPubDate($value) {
        try {
            if ($date = new DateTime($value)) {
                $value = $date;
            }
        } catch (Exception $e) {
            $value = null;
        }
        return $value;
    }

    protected function processCategory($value) {
        return array_filter(array_map('trim', explode(',', $value)));
    }

    protected function typeIsImage($type) {
    
        $imageTypes = array(
            'image',
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/png',
        );

        return in_array($type, $imageTypes);
    }

    protected function processMediaGroup($value, $key) {
        $group = array();

        if ($media = Kurogo::arrayVal($value, 'media:content')) {
            if ($thumbnail = Kurogo::arrayVal($media, 'media:thumbnail')) {
                $group['thumbnail'] = $this->processMedia($thumbnail, 'media:thumbnail', true);
            }
            $group['image'] = $this->processMedia($media, 'media:content', false);
        }

        return $group;
    }
    
    protected function processDescription($value, $key) {
        return Sanitizer::sanitizeHTML($value);
    }

    protected function processContent($value, $key) {
        return Sanitizer::sanitizeHTML($value);
    }
    
    protected function processImage($value, $key) {
        return $this->processMedia($value, $key, false);
    }

    protected function processThumbnail($value, $key) {
        return $this->processMedia($value, $key, true);
    }

    // enclosure, media, etc 
    protected function processMedia($value, $key, $thumbnail = true) {        
        $image = null;
        $type = null;
    
        if (is_array($value)) {

            $attributes = Kurogo::arrayVal($value, '@attributes', array());
            if (!$url = Kurogo::arrayVal($attributes, 'url')) {
                return null;
            }
            if (!$type = Kurogo::arrayVal($attributes, 'type')) {
                if ($medium = Kurogo::arrayVal($attributes, 'medium')) {
                    $type = $medium;
                } else {
                    $bits = parse_url($url);
                    $type = mime_type(Kurogo::arrayVal($bits, 'path'));
                }
            }

        } elseif (is_scalar($value)) {
            //assume it's a url_stat
            $url = $value;
            $bits = parse_url($url);
            $type = mime_type(Kurogo::arrayVal($bits, 'path'));
        }

        if ($this->typeIsImage($type)) {
            $image = new $this->imageClass();
            $image->setURL($url);
            $image->setThumbnail($thumbnail);
            $image->init($this->initArgs);
        }
        return $image;
    }
    
    protected function parseItem($itemData) {
        $item = new $this->itemClass();
        $item->init($this->initArgs);

        if ($this->useDescriptionForContent) {
            $itemData['content'] = Kurogo::arrayVal($itemData, 'description');            
        }

        foreach ($itemData as $key => $value) {
                    
            if ($property = $this->getPropertyForKey($key)) {
                $setMethod = "set" . $property;
                $processMethod = "process" . $property;

                if (is_callable(array($this, $processMethod))) {
                    // if we have an array of items (perhaps media), process them individually
                    if ($this->isNumericArray($value)) {
                        $tmpValue = $value;
                        $value = array();
                        foreach ($tmpValue as $v) {
                            $v = $this->$processMethod($v, $key);
                            // only include items that are non-null
                            if (isset($v)) {
                                $value[] = $v;
                            }
                        } 
                    } else {
                        $value = $this->$processMethod($value, $key);
                    }
                }

                Kurogo::log(LOG_DEBUG, "Setting $property from $key", 'rss');
                $item->$setMethod($value);
            } else {
                Kurogo::log(LOG_DEBUG, "Setting attribute $key", 'rss');
                $item->setAttribute($key, $value);
            }
        }

        return $item;
    }
    
    public function parseData($data) {
        $items = array();
        if (!$feed = parent::parseData($data, true)) {
            return $items;
        }
        if (isset($feed['channel'])) {
            $items = $this->parseChannel($feed['channel']);
        } else {
            throw new KurogoException("Don't know how to parse this feed yet");
        }

        if (is_null($this->totalItems)) {
            $this->setTotalItems(count($items));
        }

        return $items;
    }
    
    public function init($args)
    {
        parent::init($args);
        
        if (isset($args['ITEM_CLASS'])) {
            $this->setItemClass($args['ITEM_CLASS']);
        }

        if (isset($args['IMAGE_CLASS'])) {
            $this->setImageClass($args['IMAGE_CLASS']);
        }

        if (isset($args['USE_DESCRIPTION_FOR_CONTENT'])) {
            $this->setUseDescriptionForContent($args['USE_DESCRIPTION_FOR_CONTENT']);
        }

        $this->userFields = Kurogo::arrayVal($args, 'FIELDS', array());
    }
}
