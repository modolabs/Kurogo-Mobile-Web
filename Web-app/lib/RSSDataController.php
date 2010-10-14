<?php

class RSSDataController extends DataController
{
    protected $channel;
    protected $contentFilter;

    protected function cacheFolder()
    {
        return CACHE_DIR . "/RSS";
    }
    
    protected function cacheLifespan()
    {
        return $GLOBALS['siteConfig']->getVar('RSS_CACHE_LIFESPAN');
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
        $idField = $GLOBALS['siteConfig']->getVar('NEWS_STORY_ID_FIELD');
        
        foreach ($items as $item) {
            if ($item->getProperty($idField)==$id) {
                return $item;
            }
        }
        
        return null;
    }
 
    protected function clearInternalCache()
    {
        $this->channel = null;
        parent::clearInternalCache();
    }

    public function items($start=0,$limit=null, &$totalItems=0) 
    {
        if (!$this->channel) {
            $data = $this->getData();
            $this->channel = $this->parseData($data);
        }
        
        if (!$this->channel) {
            throw new Exception("Error loading rss data");
        }

        $items = $this->channel->getItems();
        
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

