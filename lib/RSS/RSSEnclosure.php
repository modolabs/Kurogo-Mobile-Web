<?php
/**
  * @package ExternalData
  * @subpackage RSS
  */

class RSSEnclosure extends XMLElement
{
    protected $name='enclosure';
    protected $url;
    protected $length;
    protected $type;

    protected function standardAttributes()
    {
        return array(
            'url',
            'length',
            'type'
        );
    }
    
    public function __construct($attribs)
    {
        $this->setAttribs($attribs);
        $this->length = $this->getAttrib('LENGTH');
        $this->type = $this->getAttrib('TYPE');
        if ($this->isImage()) {
            $url = $this->getAttrib('URL');
            $options = array();
            if(isset($attribs['THUMB_MAX_WIDTH'])) {
                $options['max_width'] = $attribs['THUMB_MAX_WIDTH'];
            }
            if(isset($attribs['THUMB_MAX_HEIGHT'])) {
                $options['max_height'] = $attribs['THUMB_MAX_HEIGHT'];
            }
            if(isset($attribs['THUMB_CROP'])) {
                $options['crop'] = $attribs['THUMB_CROP'];
            }
            $this->url = ImageLoader::cacheImage($url, $options);
        } else {
            $this->url = $this->getAttrib('URL');
        }
    }
    
    public function isImage()
    {
        $image_types = array(
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/png'
            );
        return in_array($this->type, $image_types);
    }
    
    public function getType()
    {
        return $this->type;
    }

    public function getURL()
    {
        return $this->url;
    }

    public function getLength()
    {
        return $this->length;
    }
}

