<?php
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

/**
  * @package ExternalData
  * @subpackage RSS
  */
class RSSItem extends XMLElement implements KurogoObject
{
    protected $name='item';
    protected $title;
    protected $description;
    protected $link;
    protected $guid;
    protected $pubDate;
    protected $author;
    protected $comments;
    protected $content;
    protected $category=array();
    protected $enclosure;
    protected $images=array();
    
    public function getContent()
    {
        return $this->content;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function getGUID()
    {
    	if ($this->guid) {
			return $this->guid;
		} elseif ($this->link) {
			return $this->link;
		}
    }

    public function getPubDate()
    {
        return $this->pubDate;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getCategories()
    {
        return $this->category;
    }

    public function getImages()
    {
        return $this->images;
    }
    
    public function getEnclosure()
    {
        return $this->enclosure;
    }
    
    public function getImage()
    {
        if ( ($enclosure = $this->getEnclosure()) && $enclosure->isImage()) {
            return $enclosure;
        } elseif (count($this->images)>0) {
            return $this->images[0];
        }

        return null;
    }
    
    public function addElement(XMLElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'LINK':
                if (!$value) {
                    if ($link = $element->getAttrib('HREF')) {
                        $element->setValue($link, true);
                    }
                }
                parent::addElement($element);
                break;
            case 'enclosure':
                $this->enclosure = $element;
                break;
            case 'image':
                $this->images[] = $element;
                break;
            case 'CATEGORY':
                $name = strtolower($name);
                array_push($this->$name, $value);
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }

    protected function standardAttributes()
    {
        return array(
            'title',
            'description',
            'content',
            'link',
            'author',
            'guid',
            'pubDate',
            'comments',
            'category',
            'images',
            'items'
        );
    }
    
    protected function elementMap()
    {
        return array(
            'TITLE'=>'title',
            'DESCRIPTION'=>'description',
            'LINK'=>'link',
            'GUID'=>'guid',
            'ID'=>'guid',
            'PUBDATE'=>'pubDate',
            'COMMENTS'=>'comments',
            'SUMMARY'=>'description',
            'CONTENT'=>'content',
            'CONTENT:ENCODED'=>'content',
            'BODY'=>'content',
            'DC:DATE'=>'pubDate',
            'PUBLISHED'=>'pubDate',
            'UPDATED'=>'pubDate',
            'AUTHOR'=>'author'
            
        );
    }

    function __construct($attribs)
    {
        $this->setAttribs($attribs);
    }
    
}

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
        $this->url = $this->getAttrib('URL');
        $this->length = $this->getAttrib('LENGTH');
        $this->type = $this->getAttrib('TYPE');
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
