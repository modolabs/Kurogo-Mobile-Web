<?php

class CalendarDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS='ICSDataParser';
    const DEFAULT_EVENT_CLASS='ICalEvent';
    protected $startDate;
    protected $endDate;
    protected $calendar;
    protected $requiresDateFilter=true;
    protected $contentFilter;
    protected $supportsSearch = false;
    
    public function setRequiresDateFilter($bool)
    {
        $this->requiresDateFilter = $bool ? true : false;
    }

    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search': 
                if ($this->supportsSearch) {
                    return parent::addFilter($var, $value);
                } else {
                    $this->contentFilter = $value;
                }
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    protected function cacheFolder()
    {
        return CACHE_DIR . "/Calendar";
    }
    
    protected function cacheLifespan()
    {
        return $GLOBALS['siteConfig']->getVar('ICS_CACHE_LIFESPAN');
    }

    protected function cacheFileSuffix()
    {
        return '.ics';
    }
    
    public function setStartDate(DateTime $time)
    {
        $this->startDate = $time;
    }
    
    public function startTimestamp()
    {
        return $this->startDate ? $this->startDate->format('U') : false;
    }

    public function setEndDate(DateTime $time)
    {
        $this->endDate = $time;
    }

    public function endTimestamp()
    {
        return $this->endDate ? $this->endDate->format('U') : false;
    }
    
    public function getEventCategories()
    {
        return $this->parser->getEventCategories();
    }
    
    public function setDuration($duration, $duration_units)
    {
        if (!$this->startDate) {
            return;
        } elseif (!preg_match("/^-?(\d+)$/", $duration)) {
            throw new Exception("Invalid duration $duration");
        }
        
        $this->endDate = clone($this->startDate);
        switch ($duration_units)
        {
            case 'year':
            case 'day':
            case 'month':
                $this->endDate->modify(sprintf("%s%s %s", $duration>=0 ? '+' : '', $duration, $duration_units));
                break;
            default:
                throw new Exception("Invalid duration unit $duration_units");
                break;
            
        }
    }
    
    public static function factory($args=null)
    {
        $args['CONTROLLER_CLASS'] = isset($args['CONTROLLER_CLASS']) ? $args['CONTROLLER_CLASS'] : __CLASS__;
        $args['EVENT_CLASS'] = isset($args['EVENT_CLASS']) ? $args['EVENT_CLASS'] : self::DEFAULT_EVENT_CLASS;
        $controller = parent::factory($args);
        
        return $controller;
    }

    public function getItem($id)
    {
        $this->setRequiresDateFilter(false);
        $items = $this->items();
        if (array_key_exists($id, $items)) {
            return $items[$id];
        }
        
        return false;
    }
    
    protected function clearInternalCache()
    {
        $this->calendar = null;
        parent::clearInternalCache();
    }
    
    public function items($start=0, $limit=null) 
    {
        if (!$this->calendar) {
            $data = $this->getData();
            $this->calendar = $this->parseData($data);
        }

        $events = $this->calendar->get_events();
        
        if ($this->requiresDateFilter) {
            $items = $events;
            $events = array();
            foreach ($items as $id => $event) {
                if  ((($event->get_start() >= $this->startTimestamp()) &&
                        ($event->get_start() <= $this->endTimestamp())) ||
        
                       (($event->get_end() >= $this->startTimestamp()) &&
                        ($event->get_end() <= $this->endTimestamp())) ||
        
                        (($event->get_start() <= $this->startTimestamp()) &&
                        ($event->get_end() >= $this->endTimestamp()))) 
                {
                    $events[$id] = $event;
                }
            }
        }

        if ($this->contentFilter) {
            $items = $events;
            $events = array();
            foreach ($items as $id => $event) {
                if ( (stripos($event->get_description(), $this->contentFilter)!==FALSE) || (stripos($event->get_summary(), $this->contentFilter)!==FALSE)) {
                    $events[$id] = $event;
                }
            }
        }
        
        return $this->limitItems($events, $start, $limit);
    }
}
