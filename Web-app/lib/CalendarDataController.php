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
    
    public function start_timestamp()
    {
        return $this->startDate ? $this->startDate->format('U') : false;
    }

    public function setEndDate(DateTime $time)
    {
        $this->endDate = $time;
    }

    public function end_timestamp()
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

    public function getItem($id)
    {
        $items = $this->getItems();
        if (array_key_exists($id, $items)) {
            return $items[$id];
        }
        
        return false;
    }
    
    public function items() 
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
                if  ((($event->get_start() >= $this->start_timestamp()) &&
                        ($event->get_start() <= $this->end_timestamp())) ||
        
                       (($event->get_end() >= $this->start_timestamp()) &&
                        ($event->get_end() <= $this->end_timestamp())) ||
        
                        (($event->get_start() <= $this->start_timestamp()) &&
                        ($event->get_end() >= $this->end_timestamp()))) 
                {
                    $events[$id] = $event;
                }
            }
        }
        
        return $events;
    }
}
