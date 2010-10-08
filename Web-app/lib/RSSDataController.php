<?php

class RSSDataController extends DataController
{
    protected $channel;

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
        
        if ($this->channel) {
            $items = $this->channel->getItems();
            $totalItems = count($items);
        } else {
            Debug::die_here($this);
        }

        return $this->limitItems($items, $start, $limit);
    }

}

