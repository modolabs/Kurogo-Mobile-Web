<?php

require_once(LIB_DIR . '/ICalendar.php');

class TrumbaCalendarDataController extends CalendarDataController
{
    public function url()
    {
        if (empty($this->start_date) || empty($this->end_date)) {
            throw new Exception('Start or end date cannot be blank');
        }
        
        $diff = $this->end_timestamp() - $this->start_timestamp();
        if ($diff<86400) {
            $this->requires_date_filter(true);
            $this->setFilter('startdate', $this->start_date->format('Ym').'01');
            $this->setFilter('months', 1);
        } else {
            Debug::die_here($diff);
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

