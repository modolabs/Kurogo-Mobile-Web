<?php

require_once(LIB_DIR . '/ICalendar.php');

class TrumbaCalendarDataController extends CalendarDataController
{
    protected $trumbaFilters=array();
    
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
        
        $diff = $this->end_timestamp() - $this->start_timestamp();
        if ($diff<86400) {
            if (count($this->trumbaFilters)>0) {
                $this->requiresDateFilter(false);
                $this->addFilter('startdate', $this->startDate->format('Ymd'));
                $this->addFilter('days', 1);
            } else {
                $this->requiresDateFilter(true);
                $this->addFilter('startdate', $this->startDate->format('Ym').'01');
                $this->addFilter('months', 1);
           }
        } else {
            trigger_error("Have not handled ranges greater than 1 day yet", E_USER_ERROR);
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

    public function __construct($baseURL, ICSDataParser $parser, $eventClass='TrumbaEvent')
    {
        parent::__construct($baseURL, $parser);
        $this->parser->setEventClass($eventClass);
    }
    
}

class TrumbaEvent extends ICalEvent
{
  public function set_attribute($attr, $value, $params=NULL) {
    switch ($attr) {
        case 'Contact Info':
            $values = explode(',', iCalendar::ical_unescape_text($value));
            foreach ($values as $_value) {
                $_value = trim($_value);
                if (Validator::isValidEmail($_value)) {
                    $this->set_attribute('email', $_value);
                } elseif (Validator::isValidPhone($_value)) {
                    $this->set_attribute('phone', $_value);
                } elseif (Validator::isValidURL($_value)) {
                }
            }
            break;
        case 'X-TRUMBA-CUSTOMFIELD':
            if (array_key_exists('NAME', $params)) {
                $name = $params['NAME'];
                unset($params['NAME']);
                $this->set_attribute($name, $value, $params);
            }
            break;
        default:
            parent::set_attribute($attr, $value, $params);
            break;
    }
  }

}

