<?php

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
    
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search':
                    return  (stripos($this->getTitle(), $value)!==FALSE) ||
                         (stripos($this->getDescription(), $value)!==FALSE) ||
                         (stripos($this->getContent(),     $value)!==FALSE);
                    break;
            }
        }
        
        return true;
    }
    
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
    
    public function getID() {
        return $this->getGUID();
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

