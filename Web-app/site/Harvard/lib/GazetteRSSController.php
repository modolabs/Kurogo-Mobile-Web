<?php

class GazetteRSScontroller extends RSSDataController
{
    protected $loadMore=true;
    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search':
                $this->addFilter('s',$value);
                $this->addFilter('feed', 'rss2');
                $this->loadMore = false;
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    public function getItem($id, $page=1)
    {
        $maxPages = $GLOBALS['siteConfig']->getVar('GAZETTE_NEWS_MAX_PAGES');; // to prevent runaway trains
        $idField = $GLOBALS['siteConfig']->getVar('NEWS_STORY_ID_FIELD');
        
        while ($page < $maxPages) {
            $items = $this->loadPage($page++);
            foreach ($items as $item) {
                if ($item->getProperty($idField)==$id) {
                    return $item;
                }
            }
        }            
        
        return null;
    }
    
    public function items(&$start=0,$limit=null, &$totalItems=0) 
    {
        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        $items = parent::items(0,null,$totalItems); //get all the items
        $maxPages = $GLOBALS['siteConfig']->getVar('GAZETTE_NEWS_MAX_PAGES');; // to prevent runaway trains
        
        if ($this->loadMore) {
            $page = 1;

            /* load new pages until we have enough content */
            while ( ($start > $totalItems) && ($page < $maxPages)) {
                $moreItems = $this->loadPage(++$page);
                $items = array_merge(array_values($items), array_values($moreItems));
                $totalItems += count($moreItems);
            }
            
            if ($limit) {
                $items = array_slice($items, $start, $limit); //slice off what's not needed
                
                // see if we need to fill it out at the end
                if (count($items)<$limit) {
                    $moreItems = $this->loadPage(++$page);
                    $items = array_merge($items, array_slice($moreItems,0,$limit-count($items)));
                    $totalItems += count($moreItems);
                }
            }
        } elseif ($limit) {
            $items = array_slice($items, $start, $limit); //slice off what's not needed
        }

        return $items;
    }
    
    private function loadPage($page)
    {
        $this->addFilter('paged',$page);
        $items = $this->items();
        return $items;   
    }
}

