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
    
    public static function factory($attribs) {
        $type = isset($attribs['TYPE']) ? $attribs['TYPE'] : null;
        $class = 'RSSEnclosure';
        if (self::isImage($type)) {
            $class = 'RSSImageEnclosure';
        }
        
        $element = new $class($attribs);
        return $element;
    }
    
    public function __construct($attribs)
    {
        $this->setAttribs($attribs);
        $this->length = $this->getAttrib('LENGTH');
        $this->type = $this->getAttrib('TYPE');
        $this->url = $this->getAttrib('URL');
    }
    
    protected static function isImage($type)
    {
        $image_types = array(
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/png'
            );
        return in_array($type, $image_types);
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

class RSSImageEnclosure extends RSSEnclosure implements KurogoImage
{
    public function __construct($attribs) {
        parent::__construct($attribs);
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
        if(isset($attribs['THUMB_BACKGROUND_RGB'])) {
            $options['rgb'] = $attribs['THUMB_BACKGROUND_RGB'];
        }
        $this->url = ImageLoader::cacheImage($url, $options);
    }
    
    public function getWidth() {
    }
    
    public function getHeight() {
    }
    
}

