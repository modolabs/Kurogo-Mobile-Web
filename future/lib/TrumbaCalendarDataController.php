<?php

require_once(LIB_DIR . '/ICalendar.php');

class TrumbaCalendarDataController extends CalendarDataController
{
    const DEFAULT_EVENT_CLASS='TrumbaEvent';
    protected $trumbaFilters=array();
    protected $supportsSearch = true;
    
    public function addTrumbaFilter($var, $value)
    {
        $this->trumbaFilters[$var] = $value;
        $index = count($this->trumbaFilters);
        $this->addFilter("filter" . $index, $value);
        $this->addFilter('filterfield'.$index, $var);
    }
    
    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'category':
                $this->addTrumbaFilter($GLOBALS['siteConfig']->getVar('CALENDAR_CATEGORY_FILTER_FIELD'), $value);
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    public function url()
    {
        if (empty($this->startDate) || empty($this->endDate)) {
            throw new Exception('Start or end date cannot be blank');
        }
        
        $diff = $this->endTimestamp() - $this->startTimestamp();

        if ($diff<86400 || $diff == 89999) { // fix for DST
            if (count($this->trumbaFilters)>0) {
                $this->setRequiresDateFilter(false);
                $this->addFilter('startdate', $this->startDate->format('Ymd'));
                $this->addFilter('days', 1);
            } else {
                $this->setRequiresDateFilter(true);
                $this->addFilter('startdate', $this->startDate->format('Ym').'01');
                $this->addFilter('months', 1);
           }
        } elseif ($diff % 86400 == 0) {
            $this->setRequiresDateFilter(false);
            $this->addFilter('startdate', $this->startDate->format('Ymd'));
            $this->addFilter('days', $diff / 86400);
        } else {
            trigger_error("Non day integral duration specified $diff", E_USER_ERROR);
        }
        
        return parent::url();
    }

    public function getItem($id, $time=null)
    {
        if ($time) {
            $start = new DateTime(date('Y-m-d H:i:s', $time));
            $start->setTime(0,0,0);
            $end = clone $start;
            $end->setTime(23,59,59);
        
            $this->setStartDate($start);
            $this->setEndDate($end);
            
            $items = $this->items();
            
            return isset($items[$id]) ? $items[$id] : false;
        }
    
        throw new Exception("Can't load event without a time");
    }
    
}

class TrumbaEvent extends ICalEvent
{
    protected $TrumbaCustomFields=array();
    public function set_attribute($attr, $value, $params=NULL) {
    switch ($attr) {
        case 'X-TRUMBA-CUSTOMFIELD':
            if (array_key_exists('NAME', $params)) {
                $name = $params['NAME'];
                unset($params['NAME']);
                $this->TrumbaCustomFields[$name] = $value;
                $this->set_attribute($name, $value, $params);
            }
            break;
        default:
            parent::set_attribute($attr, $value, $params);
            break;
    }
  }

}

