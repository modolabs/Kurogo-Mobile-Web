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
  * @package ExternalData
  * @subpackage RSS
  */
class RSSChannel extends XMLElement
{
    protected $name='channel';
    protected $title;
    protected $description;
    protected $link;
    protected $lastBuildDate;
    protected $pubDate;
    protected $language;
    protected $items=array();
    
    function __construct($attribs)
    {
        $this->setAttribs($attribs);
    }
    
    public function getItems()
    {
        return $this->items;
    }
  
    public function addElement(XMLElement $element)
    {
    	$name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'item':
                $this->items[] = $element;
                break;
            default:
                parent::addElement($element);
                break;
        }
    }
    
    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->title;
    }
    
    public function getLink()
    {
        return $this->link;
    }

    public function getLastBuildDate()
    {
        return $this->lastBuildDate;
    }

    public function getPubDate()
    {
        return $this->pubDate;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    protected function standardAttributes()
    {
        return array(
            'title',
            'description',
            'link',
            'lastBuildDate',
            'pubDate',
            'language',
            'items'
        );
    }

    protected function elementMap()
    {
        return array(
            'TITLE'=>'title',
            'DESCRIPTION'=>'description',
            'LINK'=>'link',
            'LASTBUILDDATE'=>'lastBuildDate',
            'PUBDATE'=>'pubDate',
            'LANGUAGE'=>'language'
        );
    }
    
    
}

