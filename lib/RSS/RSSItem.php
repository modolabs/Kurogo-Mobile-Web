<?php

/**
  * @package ExternalData
  * @subpackage RSS
  */
includePackage('News');
class RSSItem extends XMLElement implements NewsItem
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
    protected $fetchContent = false;
    protected $enclosures = array();
    protected $useDescriptionForContent = false;

    public function setFetchContent($bool) {
        $this->fetchContent =  $bool ? true : false;
    }
    public function setUseDescriptionForContent($bool){
    	$this->useDescriptionForContent = $bool ? true : false;
    }
    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search':
                    return  (stripos($this->getTitle(), $value)!==FALSE) ||
                         (stripos($this->getDescription(), $value)!==FALSE) ||
                         (stripos($this->getContent(false),     $value)!==FALSE);
                    break;
            }
        }
        
        return true;
    }
    
    public function init($args) {
        if (isset($args['FETCH_CONTENT'])) {
            $this->setFetchContent($args['FETCH_CONTENT']);
        }
        //KGO-522
        if (isset($args['USE_DESCRIPTION_FOR_CONTENT'])) {
            $this->setUseDescriptionForContent($args['USE_DESCRIPTION_FOR_CONTENT']);
        }
    }
    
    public function getContent($fetch=true)
    {
        if (strlen($this->content)==0) {
            if ($this->fetchContent && $fetch && ($url = $this->getLink())) {
                $reader = new KurogoReader($url);
                $this->content = $reader->getContent();
            }
        }

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
    
    public function getPubTimestamp() {
        if ($this->pubDate) {
            return $this->pubDate->format('U');
        }
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

    public function getEnclosures() {
        return $this->enclosures;
    }
    
    public function getImage()
    {
        foreach ($this->enclosures as $enclosure) {
            if ($enclosure instanceOf RSSImageEnclosure) {
                return $enclosure;
            }
        }
        if (count($this->images)>0) {
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
                    if ($element->getAttrib('REL') == 'enclosure') {
                        $this->enclosure = RSSEnclosure::factory(array(
                            'URL' => $element->getAttrib('HREF'),
                            'LENGTH' => $element->getAttrib('LENGTH'),
                            'TYPE' => $element->getAttrib('TYPE'),
                        ));
                        break;
                    }

                    if ($link = $element->getAttrib('HREF')) {
                        $element->shouldStripTags(true);
                        $element->setValue($link);
                    }
                }
                parent::addElement($element);
                break;
            case 'enclosure':
                $this->enclosure = $element;
                $this->enclosures[] = $element;
                break;
            case 'image':
                $this->images[] = $element;
                break;
            case 'CATEGORY':
                $name = strtolower($name);
                array_push($this->$name, $value);
                break;
            case 'PUBDATE':
            case 'DC:DATE':
            case 'PUBLISHED':
                if ($value = $element->value()) {
                    try {
                        if ($date = new DateTime($value)) {
                            $this->pubDate = $date;
                        }
                    } catch (Exception $e) {
                    }
                }
                
                break;
            case 'DESCRIPTION':
            	if($this->useDescriptionForContent){
            		parent::addElement($element);//set description
            		$element->setName('CONTENT');//set description value to content KGO-522
            	}
            	parent::addElement($element);
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

