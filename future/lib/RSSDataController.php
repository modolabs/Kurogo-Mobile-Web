<?php

class RSSDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS='RSSDataParser';
    protected $items;
    protected $contentFilter;

    protected function cacheFolder()
    {
        return CACHE_DIR . "/RSS";
    }
    
    protected function cacheLifespan()
    {
        return $GLOBALS['siteConfig']->getVar('NEWS_CACHE_LIFESPAN');
    }

    protected function cacheFileSuffix()
    {
        return '.rss';
    }

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

    public static function factory($args=null)
    {
        $args['CONTROLLER_CLASS'] = isset($args['CONTROLLER_CLASS']) ? $args['CONTROLLER_CLASS'] : __CLASS__;
        $controller = parent::factory($args);
        
        return $controller;
    }
 
    protected function clearInternalCache()
    {
        $this->items = null;
        parent::clearInternalCache();
    }

    public function items($start=0,$limit=null, &$totalItems=0) 
    {
        if (!$this->items) {
            $data = $this->getData();
            $this->items = $this->parseData($data);
            
        }
        
        $items = $this->items;
        
        if ($this->contentFilter) {
            $_items = $items;
            $items = array();
            foreach ($_items as $id=>$item) {
                if ( (stripos($item->getTitle(), $this->contentFilter)!==FALSE) || (stripos($item->getDescription(), $this->contentFilter)!==FALSE)) {
                    $items[$id] = $item;
                }
            }
        }
        
        $totalItems = count($items);

        return $this->limitItems($items, $start, $limit);
    }
}

