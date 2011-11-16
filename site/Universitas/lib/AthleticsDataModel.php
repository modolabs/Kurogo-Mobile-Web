<?php

includePackage('DataModel');
class AthleticsDataModel extends ItemListDataModel
{
    protected $DEFAULT_PARSER_CLASS='RSSDataParser';
    protected $startDate;
    protected $endDate;
    
    protected $cacheFolder = 'Athletics';
    
    public function setStartDate(DateTime $time) {
        $this->startDate = $time;
        $this->setOption('startDate', $this->startDate);        
    }
    
    public function getStartDate() {
        return $this->startDate;
    }
    
    public function getStartTimestamp() {
        return $this->startDate ? $this->startDate->format('U') : 0;
    }

    public function setEndDate(DateTime $time) {
        $this->endDate = $time;
        $this->setOption('endDate', $this->endDate);
    }
    
    public function getEndTimestamp()
    {
        return $this->endDate ? $this->endDate->format('U') : false;
    }
    
    public function getEndDate() {
        return $this->endData;
    }
    
    public function getItemForSchedule($id, $time=null) {
        //use the time to limit the range of events to seek (necessary for recurring events)
        if ($time = filter_var($time, FILTER_VALIDATE_INT)) {
            $start = new DateTime(date('Y-m-d H:i:s', $time));
            $start->setTime(0,0,0);
            $end = clone $start;
            $end->setTime(23,59,59);
            $this->setStartDate($start);
            $this->setEndDate($end);
        }
        
        $items = $this->itemsForSchedule();
		foreach($items as $key => $item) {
			if($id == $item->getID()) {
				return $item;
			}
		}
		
        return false;
    }
    
    public function itemsForSchedule() {

        $calendar = $this->getParsedData();
        $startTimestamp = $this->getStartTimestamp() ? $this->getStartTimestamp() : CalendarDataController::START_TIME_LIMIT;
        $endTimestamp = $this->getEndTimestamp() ? $this->getEndTimestamp() : CalendarDataController::END_TIME_LIMIT;
        $range = new TimeRange($startTimestamp, $endTimestamp);

        $events = $calendar->getEventsInRange($range, $this->getLimit());
        return $this->limitItems($events, $this->getStart(), $this->getLimit());
    }
}