<?php
/**
 * CalendarDataController
 * @package ExternalData
 * @subpackage Calendar
 */

/**
 * Retrieves and parses calendar data
 * @package ExternalData
 * @subpackage Calendar
 */
includePackage('DataModel');

class CalendarDataModel extends ItemListDataModel
{
    protected $DEFAULT_PARSER_CLASS='ICSDataParser';
    const START_TIME_LIMIT=-2147483647; 
    const END_TIME_LIMIT=2147483647; 
    protected $cacheFolder = 'Calendar';
    protected $startDate;
    protected $endDate;
    protected $calendar;
    protected $filters=array();
    
    public function setRequiresDateFilter($bool)
    {
        $this->requiresDateFilter = $bool ? true : false;
    }

    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'category':
                $this->filters[$var] = $value;
                break;
        }
        
        $this->retriever->addFilter($var, $value);
    }
    
    public function setStartDate(DateTime $time)
    {
        $clearCache = $this->startDate && $time->format('U') < $this->startTimestamp();
        
        $this->startDate = $time;
        
        if ($clearCache) {
          $this->clearInternalCache();
        }
        
        $this->setOption('startDate', $this->startDate);        
    }
    
    public function startTimestamp()
    {
        return $this->startDate ? $this->startDate->format('U') : false;
    }
    
    public function getStartDate() {
        return $this->startDate;
    }

    public function getEndDate() {
        return $this->endDate;
    }

    public function setEndDate(DateTime $time)
    {
        $clearCache = $this->endDate && $time->format('U') > $this->endTimestamp();
        
        $this->endDate = $time;
        
        if ($clearCache) {
          $this->clearInternalCache();
        }

        $this->setOption('endDate', $this->endDate);        
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
            throw new KurogoDataException("Invalid duration $duration");
        }
        
        $endDate = clone($this->startDate);
        switch ($duration_units)
        {
            case 'year':
            case 'day':
            case 'month':
                $endDate->modify(sprintf("%s%s %s", $duration>=0 ? '+' : '', $duration, $duration_units));
                break;
            default:
                throw new KurogoDataException("Invalid duration unit $duration_units");
                break;
            
        }
        
        $this->setEndDate($endDate);
    }
    
    protected function init($args)
    {
        parent::init($args);
    }
    
    public function getNextEvent($todayOnly=false) {
        $start = new DateTime();
        $start->setTime(date('H'), floor(date('i')/5)*5, 0);
        $this->setStartDate($start);
        if ($todayOnly) {
            $end = new DateTime();
            $end->setTime(23,59,59);
            $this->setEndDate($end);
        } else {
            $this->endDate = null;
            $this->setOption('endDate', null);
        }

        $event = $this->getItemByIndex(0);
        return $event;
    }

    public function getPreviousEvent($todayOnly=false) {
        $end = new DateTime();
        $end->setTime(date('H'), floor(date('i')/5)*5, 0);
        $this->setEndDate($end);
        if ($todayOnly) {
            $start = new DateTime();
            $start->setTime(0,0,0);
            $this->setStartDate($start);
        } else {
            $this->startDate = null;
            $this->setOption('startDate', null);
        }

        $items = $this->items();
        return end($items);
    }
    
    public function getItem($id, $time=null)
    {
        //use the time to limit the range of events to seek (necessary for recurring events)
        if ($time = filter_var($time, FILTER_VALIDATE_INT)) {
            $start = new DateTime(date('Y-m-d H:i:s', $time));
            $start->setTime(0,0,0);
            $end = clone $start;
            $end->setTime(23,59,59);
            $this->setStartDate($start);
            $this->setEndDate($end);
        }
        
        $items = $this->items();
		foreach($items as $key => $item) {
			if($id == $item->getID()) {
				return $item;
			}
		}
        
        return false;
    }
    
    public function getEvent($id) {
        $calendar = $this->getCalendar();
        return $calendar->getEvent($id);
    }
    
    protected function getCalendar() {
        if (!$this->calendar) {
            $calendar = $this->retriever->getData();
            if (!$calendar instanceOf CalendarInterface) {
                throw new KurogoDataException('Return value is not a valid calendar');
            }
            $this->calendar = $calendar;
        }
        return $this->calendar;
    }
    
    public function items() {

        $calendar = $this->getCalendar();

        $startTimestamp = $this->startTimestamp() ? $this->startTimestamp() : CalendarDataController::START_TIME_LIMIT;
        $endTimestamp = $this->endTimestamp() ? $this->endTimestamp() : CalendarDataController::END_TIME_LIMIT;
        $range = new TimeRange($startTimestamp, $endTimestamp);
        
        $events = $calendar->getEventsInRange($range, $this->getLimit(), $this->filters);
        return $this->limitItems($events, $this->getStart(), $this->getLimit());
    }
    
    public function clearInternalCache()
    {
        $this->calendar = null;
        parent::clearInternalCache();
    }

    public function search($searchTerms) {
        if ($this->retriever instanceOf SearchDataRetriever) {
            $calendar = $this->retriever->search($searchTerms, $response);
            $items = $calendar->getEvents();

            if ($totalItems = $response->getContext('totalItems')) {
                $this->setTotalItems($totalItems);
            }

            return $items;
        } else {
            return parent::search($searchTerms);
        }
    }
}
