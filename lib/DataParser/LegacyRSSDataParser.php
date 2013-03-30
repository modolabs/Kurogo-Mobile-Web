<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package ExternalData
  * @subpackage RSS
  */

/**
  */
includePackage('RSS');

/**
  * @package ExternalData
  * @subpackage RSS
  */
class LegacyRSSDataParser extends XMLDataParser
{
    protected $channel;
    protected $channelClass='RSSChannel';
    protected $itemClass='RSSItem';
    protected $imageClass='RSSImage';
    protected $enclosureClass='RSSEnclosure';
    protected $imageEnclosureClass='RSSImageEnclosure';
    protected $removeDuplicates = false;
    protected $htmlEscapedCDATA = false;
    protected $useDescriptionForContent = false;
    protected $items=array();
    protected $guids=array();

    protected static $startElements=array(
        'RSS', 'RDF:RDF', 'CHANNEL', 'FEED', 'ITEM', 'ENTRY',
        'ENCLOSURE', 'MEDIA:THUMBNAIL','MEDIA:CONTENT', 'IMAGE', 'LINK');
    protected static $endElements=array(
        'CHANNEL', 'FEED', 'ITEM', 'ENTRY', 'DESCRIPTION');
    
    public function items()
    {
        return $this->items;
    }
    
    public function getTitle() {
        return $this->channel->getTitle();
    }

    public function clearInternalCache() {
    	parent::clearInternalCache();
    	$this->items = array();
    	$this->guids = array();
    }

    public function init($args)
    {
        parent::init($args);
        
        if (isset($args['CHANNEL_CLASS'])) {
            $this->setChannelClass($args['CHANNEL_CLASS']);
        }

        if (isset($args['ITEM_CLASS'])) {
            $this->setItemClass($args['ITEM_CLASS']);
        }

        if (isset($args['IMAGE_CLASS'])) {
            $this->setImageClass($args['IMAGE_CLASS']);
        }

        if (isset($args['ENCLOSURE_CLASS'])) {
            $this->setEnclosureClass($args['ENCLOSURE_CLASS']);
        }
        
        if (isset($args['IMAGE_ENCLOSURE_CLASS'])) {
            $this->setImageEnclosureClass($args['IMAGE_ENCLOSURE_CLASS']);
        }

        if (isset($args['REMOVE_DUPLICATES'])) {
            $this->removeDuplicates = $args['REMOVE_DUPLICATES'];
        }

        if (isset($args['HTML_ESCAPED_CDATA'])) {
            $this->htmlEscapedCDATA = $args['HTML_ESCAPED_CDATA'];
        }
        
        if (isset($args['USE_DESCRIPTION_FOR_CONTENT'])) {
            $this->setUseDescriptionForContent($args['USE_DESCRIPTION_FOR_CONTENT']);
        }
    }
    
    public function setUseDescriptionForContent($bool) {
    	$this->useDescriptionForContent = $bool ? true : false;
    }

    protected function shouldHandleStartElement($name)
    {
        return in_array($name, self::$startElements);
    }

    protected function handleStartElement($name, $attribs)
    {
        switch ($name)
        {
            case 'RSS':
            case 'RDF:RDF':
                break;
            case 'CHANNEL':
            case 'FEED': //for atom feeds
                $this->elementStack[] = new $this->channelClass($attribs);
                break;
            case 'ITEM':
            case 'ENTRY': //for atom feeds
                $element = new $this->itemClass($attribs);
                $element->init($this->initArgs);
                $this->elementStack[] = $element;
                break;
            case 'ENCLOSURE':
            case 'MEDIA:CONTENT':
            case 'MEDIA:THUMBNAIL':
                if ($this->enclosureIsImage($name, $attribs)) {
                    $element = new $this->imageEnclosureClass($attribs);
                } else {
                    $element = new $this->enclosureClass($attribs);
                }
                $element->init($this->initArgs);
                $this->elementStack[] = $element;
                break;
            case 'LINK':
                if (isset($attribs['REL'], $attribs['HREF']) && $attribs['REL'] == 'enclosure') {
                    $attribs['URL'] = $attribs['HREF'];
                    if ($this->enclosureIsImage($name, $attribs)) {
                        $element = new $this->imageEnclosureClass($attribs);
                    } else {
                        $element = new $this->enclosureClass($attribs);
                    }
                    $element->init($this->initArgs);
                    $element->setName('enclosure');
                } else {
                    $element = new XMLElement($name, $attribs, $this->getEncoding());
                }
                $this->elementStack[] = $element;
                break;
            case 'IMAGE':
                $this->elementStack[] = new $this->imageClass($attribs);
                break;
        }
    }

    protected function shouldHandleEndElement($name)
    {
        return in_array($name, self::$endElements);
    }

    protected function handleEndElement($name, $element, $parent)
    {
        switch ($name)
        {
            case 'FEED': //for atom feeds
            case 'CHANNEL':
                $this->channel = $element;
                break;
            case 'ITEM':
            case 'ENTRY': //for atom feeds
                if (!$this->removeDuplicates || !in_array($element->getGUID(), $this->guids)) {
                    $this->guids[] = $element->getGUID();
                    $this->items[] = $element;
                }
                break;
            case 'DESCRIPTION':
                $parent->addElement($element); // add description as description
                
                if ($this->useDescriptionForContent) {
                    // add description element again as content
                    $element->setName('CONTENT');
                    $element->shouldStripTags($this->shouldStripTags($element));
                    $element->shouldHTMLDecodeCDATA($this->shouldHTMLDecodeCDATA($element));
                    $parent->addElement($element);
                }
                break;
        }
    }

    public function setChannelClass($channelClass)
    {
    	if ($channelClass) {
    		if (!class_exists($channelClass)) {
    			throw new KurogoConfigurationException("Cannot load class $channelClass");
    		}
			$this->channelClass = $channelClass;
		}
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

    public function setEnclosureClass($enclosureClass)
    {
    	if ($enclosureClass) {
    		if (!class_exists($enclosureClass)) {
    			throw new KurogoConfigurationException("Cannot load class $enclosureClass");
    		}
			$this->enclosureClass = $enclosureClass;
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
    
    protected function shouldStripTags($element)
    {
        $strip_tags = true;
        switch ($element->name())
        {
            case 'CONTENT:ENCODED':
            case 'CONTENT':
            case 'BODY':
            case 'DESCRIPTION':
                $strip_tags = false;
                break;
        }
        
        return $strip_tags;
    }
    
    protected function shouldHTMLDecodeCDATA($element)
    {
        $html_decode = false;
        
        if ($this->htmlEscapedCDATA) {
            // Some buggy feeds have HTML escaped with both CDATA and html entities
            switch ($element->name()) {
                case 'CONTENT:ENCODED':
                case 'CONTENT':
                case 'BODY':
                    $html_decode = true;
                    break;
            }
        }
        return $html_decode;
    }
    
    protected function enclosureIsImage($name, $attribs)
    {
        $imageTypes = array(
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/png',
        );
        $type   = isset($attribs['TYPE'])   ? $attribs['TYPE']   : '';
        $medium = isset($attribs['MEDIUM']) ? $attribs['MEDIUM'] : '';
        
        return in_array($type, $imageTypes) || $name == 'MEDIA:THUMBNAIL' || ($name == 'MEDIA:CONTENT' && $medium == 'image');
    }

}
