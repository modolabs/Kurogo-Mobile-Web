<?php
/**
  * @package ExternalData
  * @subpackage RSS
  */

/**
  * @package ExternalData
  * @subpackage RSS
  */
includePackage('RSS');
class RSSDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS='RSSDataParser';
    protected $items;
    protected $contentFilter;
    protected $cacheFolder = 'RSS';

    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search': 
            //sub classes should override this if there is a more direct way to search. Default implementation is to iterate through each item
                $this->contentFilter = $value;
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    public function getItem($id)
    {
        if (!$id) {
            return null;
        }
        
        $items = $this->items();
        
        foreach ($items as $item) {
            if ($item->getGUID()==$id) {
                return $item;
            }
        }
        
        return null;
    }

    protected function clearInternalCache()
    {
        $this->items = null;
        parent::clearInternalCache();
    }
    
    public function getTitle() {
        if (!$this->title) {
            if ($this->parser) {
                return $this->parser->getTitle();
            }
        }
        
        return $this->title;
    }


    protected function _items($start=0,$limit=null)
    {
        if (!$this->items) {
            $this->items = $this->getParsedData();
        }
        
        $items = $this->items;
        
        if ($this->contentFilter) {
            $_items = $items;
            $items = array();
            foreach ($_items as $id=>$item) {
                if ( (stripos($item->getTitle(),       $this->contentFilter)!==FALSE) ||
                     (stripos($item->getDescription(), $this->contentFilter)!==FALSE) ||
                     (stripos($item->getContent(),     $this->contentFilter)!==FALSE) ) {
                    $items[$id] = $item;
                }
            }
        }

        $this->totalItems = count($items);
        return $this->limitItems($items, $start, $limit);
    }
}

