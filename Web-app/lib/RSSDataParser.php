<?php

class RSSDataParser extends DataParser
{
    protected $root;
    protected $elementStack = array();
    protected $channelClass='RSSChannel';
    protected $itemClass='RSSItem';
    protected $imageClass='RSSImage';

    protected function startElement($xml_parser, $name, $attribs)
    {
        switch ($name)
        {
            case 'RSS':
                break;
            case 'CHANNEL':
                $this->elementStack[] = new $this->channelClass($attribs);
                break;
            case 'ITEM':
                $this->elementStack[] = new $this->itemClass($attribs);
                break;
            case 'IMAGE':
                $this->elementStack[] = new $this->imageClass($attribs);
                break;
            default:
                $this->elementStack[] = new RSSElement($name, $attribs);
        }
    }

    public function setChannelClass($channelClass)
    {
        $this->channelClass = $channelClass;
    }

    public function setItemClass($itemClass)
    {
        $this->itemClass = $itemClass;
    }

    public function setImageClass($imageClass)
    {
        $this->imageClass = $imageClass;
    }

    protected function endElement($xml_parser, $name)
    {
        if ($element = array_pop($this->elementStack)) {
            if ($parent = end($this->elementStack)) {
                $parent->addElement($element);
            } else {
                $this->root = $element;
            }
        }
    }

    protected function characterData($xml_parser, $data)
    {
        $data = trim($data);
        if ($data) {
            if ($element = end($this->elementStack)) {
                $element->appendValue($data);
            }
        }
    }
    
  public function parseData($contents) {
        $xml_parser = xml_parser_create();
        // use case-folding so we are sure to find the tag in $map_array
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, true);
        
        xml_set_element_handler($xml_parser, array($this,"startElement"), array($this,"endElement"));
        xml_set_character_data_handler($xml_parser, array($this,"characterData"));
        
        if (!xml_parse($xml_parser, $contents)) {
            throw new Exception(sprintf("XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
        return $this->root;
       }
}

class RSSElement
{
    protected $attribs=array();
    protected $name;
    protected $value;
    protected $debugMode = false;
    protected $properties = array();
    
    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode ? true : false;
    }
    
    public function __construct($name, $attribs)
    {
        $this->setName($name);
        $this->setAttribs($attribs);
    }
    
    public function setAttribs($attribs)
    {
        if (is_array($attribs)) {
            $this->attribs = $attribs;
        }
    }
    
    public function getAttribs()
    {
        return $this->attribs;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function appendValue($value)
    {
        $this->value .= $value;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function name()
    {
        return $this->name;
    }

    public function value()
    {
        return $this->value;
    }
    
    protected function elementMap()
    {
        return array();
    }
    
    protected function standardAttributes()
    {
        return array();
    }
    
    public function getProperty($var)
    {
        if (in_array($var, $this->standardAttributes())) {
            return $this->$var;
        } elseif (array_key_exists(strtoupper($var), $this->properties)) {
            return $this->properties[strtoupper($var)]->value();
        }
    }
 
    public function addElement(RSSElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        $map = $this->elementMap();
        
        if (array_key_exists($name, $map)) {
            $this->$map[$name] = $value;
        } else {
            $this->properties[$name] = $element;
       }
    }
    
}

class RSSChannel extends RSSElement
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
    
    public function addElement(RSSElement $element)
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

class RSSItem extends RSSElement
{
    protected $name='item';
    protected $title;
    protected $description;
    protected $link;
    protected $guid;
    protected $pubDate;
    protected $comments;
    protected $category=array();
    protected $images=array();

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function getGUID()
    {
        return $this->guid;
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
    
    public function getImage()
    {
        return count($this->images)>0 ? $this->images[0] : null;
    }
    
    public function addElement(RSSElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
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
            'link',
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
            'PUBDATE'=>'pubDate',
            'COMMENTS'=>'comments'
        );
    }

    function __construct($attribs)
    {
        $this->setAttribs($attribs);
    }
    
}

class RSSImage extends RSSElement
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

