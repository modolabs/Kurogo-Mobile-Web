<?php

require_once(LIB_DIR . '/RSS.php');

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
        
        while ($page < $maxPages) {
            $items = $this->loadPage($page++);
            foreach ($items as $item) {
                if ($item->getGUID()==$id) {
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

class GazetteRSSItem extends RSSItem
{
    protected function elementMap()
    {
        $elementMap = array_merge(parent::elementMap(), array(
            'HARVARD:WPID'=>'guid',
            'CONTENT:ENCODED'=>'content'
            )
        );
        
        return $elementMap;
    }
    
    public function addElement(RSSElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'image':
                if ($element->getWidth()>1) {
                    $this->images[] = $element;
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }
}

class GazetteRSSImage extends RSSImage
{
    private function cacheFilename()
    {
        return md5($this->url);
    }

    protected function cacheFolder()
    {
        return CACHE_DIR . "/GazetteImages";
    }
    
    protected function cacheLifespan()
    {
        return $GLOBALS['siteConfig']->getVar('GAZETTE_NEWS_IMAGE_CACHE_LIFESPAN');
    }

    protected function cacheFileSuffix()
    {
        $extension = pathinfo($this->url, PATHINFO_EXTENSION);
        return $extension ? '.' . $extension : '';
    }

    private function cacheImage()
    {
        if (!$this->url) {
            return;
        }
        
        $cacheFilename = $this->cacheFilename();
        $cache = new DiskCache($this->cacheFolder(), $this->cacheLifespan(), TRUE);
        $cache->setSuffix($this->cacheFileSuffix());
        $cache->preserveFormat();
        
        if (!$cache->isFresh($cacheFilename)) {
            if ($data = file_get_contents($this->url)) {
                $cache->write($data, $cacheFilename);
            }
        }

        if ($image_size = getimagesize($cache->getFullPath($cacheFilename))) {
            $this->width = intval($image_size[0]);
            $this->height = intval($image_size[1]);
        }
        
    }
    
    public function addElement(RSSElement $element)
    {
        switch ($element->name())
        {
            case 'WIDTH':
            case 'HEIGHT':
                /* ignore width and height because it lies */
                break;
            case 'URL':
                $this->url = $element->value();
                $this->cacheImage();
                break;
            default:
                return parent::addElement($element);
        }
    }
}
