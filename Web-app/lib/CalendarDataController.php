<?php

class CalendarDataController extends DataController
{
    protected $startDate;
    protected $endDate;
    protected $calendar;
    protected $requiresDateFilter=false;
    
    public function requiresDateFilter($bool)
    {
        $this->requiresDateFilter = $bool ? true : false;
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

    public function __construct($baseURL, ICSDataParser $parser, $eventClass='ICalEvent')
    {
        parent::__construct($baseURL, $parser);
        $this->parser->setEventClass($eventClass);
    }

    public function getItem($id)
    {
        $items = $this->getItems();
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
        
        return $this->limitItems($events, $start, $limit);
    }
}
