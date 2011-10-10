<?php
/**
  * @package ExternalData
  * @subpackage Calendar
  */

/**
  * @package ExternalData
  * @subpackage Calendar
  */
class TrumbaCalendarDataController extends CalendarDataController
{
    const DEFAULT_EVENT_CLASS='TrumbaEvent';
    protected $trumbaFilters=array();
    protected $supportsSearch = true;
    protected $categoryFilter = '';
    
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
                if ($this->categoryFilter) {
                    $this->addTrumbaFilter($this->categoryFilter, $value);
                }
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    public function url()
    {
        if (empty($this->startDate) || empty($this->endDate)) {
            throw new KurogoConfigurationException('Start or end date cannot be blank');
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
            Kurogo::log(LOG_WARNING, "Non day integral duration specified $diff", 'calendar');
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
            
            $items = $this->events();
            foreach ($items as $key => $item) {
                if ($id == $item->get_uid()) {
                    return $item;
                }
            }
        }
    
        throw new KurogoConfigurationException("Can't load event without a time");
    }
    
    protected function init($args)
    {
        parent::init($args);
        if (isset($args['CATEGORY_FILTER_FIELD'])) {
          $this->categoryFilter = $args['CATEGORY_FILTER_FIELD'];
        }
    }
}

/**
  * @package ExternalData
  * @subpackage Calendar
  */
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

