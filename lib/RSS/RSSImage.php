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
class RSSImage extends XMLElement implements KurogoImage
{
    protected $name='image';
    protected $title;
    protected $link;
    protected $url;
    protected $width;
    protected $height;
    
    function __construct($attribs)
    {
        $this->setAttribs($attribs);
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function getURL()
    {
        return $this->url;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    protected function standardAttributes()
    {
        return array(
            'title',
            'link',
            'url',
            'width',
            'height'
        );
    }

    protected function elementMap()
    {
        return array(
            'TITLE'=>'title',
            'LINK'=>'link',
            'URL'=>'url',
            'WIDTH'=>'width',
            'HEIGHT'=>'height'
        );
    }
}
