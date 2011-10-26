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
includePackage('DataController');

class CalendarDataController extends ItemsDataController
{
    protected $DEFAULT_PARSER_CLASS='ICSDataParser';
    const DEFAULT_EVENT_CLASS='ICalEvent';
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
        
        $this->endDate = clone($this->startDate);
        switch ($duration_units)
        {
            case 'year':
            case 'day':
            case 'month':
                $this->endDate->modify(sprintf("%s%s %s", $duration>=0 ? '+' : '', $duration, $duration_units));
                $this->clearInternalCache();
                break;
            default:
                throw new KurogoDataException("Invalid duration unit $duration_units");
                break;
            
        }
    }
    
    protected function init($args)
    {
        $args['EVENT_CLASS'] = isset($args['EVENT_CLASS']) ? $args['EVENT_CLASS'] : self::DEFAULT_EVENT_CLASS;
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
        }

        $event = $this->getItemByIndex(0);
        return $event;
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
			if($id == $item->get_uid()) {
				return $item;
			}
		}
        
        return false;
    }
    
    public function getEvent($id) {
        if (!$this->calendar) {
            $this->calendar = $this->getParsedData();
        }
        
        return $this->calendar->getEvent($id);
    }
    
    public function items($start=0, $limit=null)
    {
        if (!$this->calendar) {
            $this->calendar = $this->getParsedData();
        }

        $startTimestamp = $this->startTimestamp() ? $this->startTimestamp() : CalendarDataController::START_TIME_LIMIT;
        $endTimestamp = $this->endTimestamp() ? $this->endTimestamp() : CalendarDataController::END_TIME_LIMIT;
        $range = new TimeRange($startTimestamp, $endTimestamp);
        
        $events = $this->calendar->getEventsInRange($range, $limit, $this->filters);
        return $this->limitItems($events, $start, $limit);
    }
    
    protected function clearInternalCache()
    {
        $this->calendar = null;
        parent::clearInternalCache();
    }

    public function search($searchTerms, $start=0, $limit=null) {
        if ($this->retriever->supportsSearch()) {
            $response = $this->retriever->search($searchTerms);
            $calendar = $this->parseData($response->getResponse(), $this->parser);
            $events = $calendar->getEvents();
        } else {
        
            $items = $this->items($start, $limit);
            $events = array();
            foreach ($items as $occurrence) {
                if ( (stripos($occurrence->get_description(), $searchTerms)!==FALSE) || (stripos($occurrence->get_summary(), $searchTerms)!==FALSE)) {
                    $events[] = $occurrence;
                }
            }
        }
        
        return $this->limitItems($events, $start, $limit);
    }
}
