<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * CalendarDataModel
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
    protected $filterCategoryByDay;
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

    public function getEventsByCategory($cateID) {
        $limit = $this->getLimit();
        $this->setLimit(null);
        $items = $this->items();
        $events = array();
        foreach($items as $item) {
            $eventCategories = $item->getEventCategories();
            if(in_array($cateID, $eventCategories)) {
                $events[] = $item;
            }
        }
        if ($limit) {
            $events = $this->limitItems($events, 0, $limit);
        }
        return $events;
    }
    
    public function getEventCategories($limit = 0)
    {
        $this->setLimit(null);
        $items = $this->items();
        $categories = array();
        foreach ($items as $item) {
            if ($eventCategories = $item->getEventCategories()) {
                $categories = array_merge($categories, $eventCategories);
            }
        }

        if ($limit) {        
            //get the count for each category
            $categoriesCount = array_count_values($categories);
            arsort($categoriesCount); //sort by count
            
            //limit the number of categories
            $categoriesCount = array_slice($categoriesCount, 0, $limit);
            $categories = array_keys($categoriesCount);
        } else {
            $categories = array_unique($categories);
        }

        //sort alphabetically    
        natsort($categories);
        $cats = array();
        foreach($categories as $category) {
            $catObj = new ICalCategory();
            $catObj->setID($category);
            $catObj->setName($category);
            $cats[] = $catObj;
        }
        return $cats;
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
        $this->setFilterCateByDay($args);
    }

    protected function setFilterCateByDay($args) {
        if(isset($args['FILTER_CATEGORY_BY_DAY'])) {
            $this->filterCategoryByDay = (boolean) $args['FILTER_CATEGORY_BY_DAY'];
        }else {
            $this->filterCategoryByDay = false;
        }
    }

    public function filterCategoryByDay() {
        return $this->filterCategoryByDay;
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
    
    public function getItem($id, $time=null) {
    
        if ($this->retriever instanceOf ItemDataRetriever) {
            $item = $this->retriever->getItem($id, $response);
            return $item;
        }
        
        //use the time to limit the range of events to seek (necessary for recurring events)
        if ($time = filter_var($time, FILTER_VALIDATE_INT)) {
            $start = new DateTime(date('Y-m-d H:i:s', $time));
            $end = clone $start;
            $end->setTime(23,59,59);
            $this->setStartDate($start);
            $this->setEndDate($end);
            $this->setOption('time', $time);
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

        $startTimestamp = $this->startTimestamp() ? $this->startTimestamp() : CalendarDataModel::START_TIME_LIMIT;
        $endTimestamp = $this->endTimestamp() ? $this->endTimestamp() : CalendarDataModel::END_TIME_LIMIT;
        $range = new TimeRange($startTimestamp, $endTimestamp);
        $events = $calendar->getEventsInRange($range, $this->getLimit(), $this->filters);
        //set total items number
        $this->setTotalItems(count($events));
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
