<?php

class AthleticEvent extends KurogoDataObject {

    protected $sport;
    protected $sportName;
    protected $startDate;
    protected $allDay = false;
    protected $noTime = false;
    protected $range;
    protected $location;
    protected $link;
    protected $gender;
    
    public function setAllDay($allDay) {
        $this->allDay = (bool) $allDay;
    }

    public function setNoTime($noTime) {
        $this->noTime = (bool) $noTime;
    }

    public function isAllDay() {
        $range = $this->getRange();
        
        return $range instanceOf DayRange;
    }
    
    public function setSport($sport) {
        $this->sport = $sport;
    }
    
    public function getSport() {
        return $this->sport;
    }
    
    public function setSportName($name) {
        $this->sportName = $name;
    }
    
    public function getSportName() {
        return $this->sportName;
    }
    
    public function setGender($gender) {
        $this->gender = $gender;
    }
    
    public function getGender() {
        return $this->gender;
    }
    
   
    public function setLocation($location) {
        $this->location = $location;
    }
    
    public function getLocation() {
        return $this->location;
    }
    
    public function setLink($link) {
        $this->link = $link;
    }
    
    public function getLink() {
        return $this->link;
    }

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search': //case insensitive
                    return  (stripos($this->getSportFullName(), $value)!==FALSE) ||
                    (stripos($this->getOpponent(), $value)!==FALSE);
                    break;
                case 'sport':
                    return strtolower($this->getSport()) === strtolower($value);
                    break;
            }
        }   
        
        return true;     
    }

    public function setStartDate(DateTime $start) {
        $this->startDate = $start;
    }

    public function getStartDate() {
        return $this->startDate;
    }
        
    public function getStartTime() {
        if ($this->startDate) {
            return $this->startDate->format('U');
        }
    }
    
    public function getRange() {
        if (!$this->range) {
            if ($startTime = $this->getStartTime()) {
                if ($this->allDay || $this->noTime) {
                    $this->range = new DayRange($startTime);
                } else {
                    $this->range = new TimeRange($startTime);
                }
            }
        }
        
        return $this->range;
    }
    
}