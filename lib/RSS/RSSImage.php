<?php
/**
  * @package ExternalData
  * @subpackage RSS
  */

class RSSImage extends XMLElement
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
