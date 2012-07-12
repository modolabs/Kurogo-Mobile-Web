<?php

class CSTVDataRetriever extends URLDataRetriever
{
    protected $sport;
    
    protected $DEFAULT_PARSER_CLASS='CSTVDataParser';
    
    protected function initResponse() {
        $response = parent::initResponse();
        //$response->setContext('sport', $this->sport);
        return $response;
    }
    
    protected function init($args) {
        parent::init($args);
        if (!isset($args['SPORT'])) {
            throw new KurogoConfigurationException("Sport must be defined");
        }
        $this->sport = $args['SPORT'];
    }

}

class CSTVDataParser extends XMLDataParser {

    protected $sport;
    
    protected static $startElements=array();
    protected static $endElements=array('EVENT');

    public function clearInternalCache() {
        parent::clearInternalCache();
        $this->items = new AthleticCalendar();
    }
    
    public function init($args) {
        parent::init($args);
        if (isset($args['SPORT'])) {
            $this->sport = $args['SPORT'];
        }
    }
    
    protected function shouldHandleStartElement($name) {
        return in_array($name, self::$startElements);
    }

    protected function handleStartElement($name, $attribs) {
        return false;
    }

    protected function shouldHandleEndElement($name) {
        return in_array($name, self::$endElements);
    }
    
    protected function handleEndElement($name, $element, $parent) {
        switch ($name) {
            case 'EVENT':
                $event = $this->parseEntry($element);
                $filters = array();
                if ($this->sport) {
                    $filters['sport'] = $this->sport;
                }
                if ($event->filterItem($filters)) {
                    $this->items->addEvent($event);
                }
                break;
        }
    }

    protected function timeZoneMap() {
        return array(
            'ET' => 'America/New_York',
            'CT' => 'America/Chicago',
            'MT' => 'America/Denver',
            'PT' => 'America/Los_Angeles'
        );
    }
    
    protected function transformTimeZone($timezone) {
        $timeZoneMap = $this->timeZoneMap();
        return $timezone && isset($timeZoneMap[$timezone]) ? $timeZoneMap[$timezone] : '';
    }
    
    protected function parseEntry($entry) {
        static $eventNum;
        
        $eventNum++;
        $event = new CSTVAthleticEvent();
        if ($id = $entry->getAttrib('ID')) {
            $event->setID($id);
        } else {
            $event->setID($eventNum);
        }
        
        $event->setSport($entry->getProperty('SPORT'));
        $event->setSportName($entry->getProperty('SPORT_FULLNAME'));
        $event->setSchool($entry->getProperty('SCHOOL'));
        $event->setOpponent($entry->getProperty('OPPONENT'));
        $event->setHomeAway($entry->getProperty('HOME_VISITOR'));
        $event->setLocation($entry->getProperty('LOCATION'));
        $event->setScore($entry->getProperty('OUTCOME_SCORE'));
        $event->setLink($entry->getProperty('RECAP'));
        //set the gender
        if ($sportName = $entry->getProperty('SPORT')) {
            $prefix = strtoupper(substr($sportName, 0, 1));
            if ($prefix == 'W' || $prefix == 'M') {
                $event->setGender($prefix);
            }
        }
        
        //convert the sport datetime
        $sportDate = $entry->getProperty('EVENT_DATE');
        $time = $entry->getProperty('TIME');
        $curtimeZone = $entry->getProperty('TIME_ZONE');
        if ($sportDate) {
            //format the time data
            switch ($time) {
                case 'All Day':
                    $event->setAllDay(true);
                    $time = '';
                    break;
                case 'TBA':
                    $event->setNoTime(true);
                    $time = '';
                    break;
            }
            
            $strDate = $time ? $sportDate . ' ' . $time : $sportDate;
            if ($timeZoneData = $this->transformTimeZone($curtimeZone)) {
                $timeZone = new DateTimeZone($timeZoneData);
            } else {
                $timeZone = Kurogo::siteTimezone();
            }
            //save the event time to datetime object
            $event->setStartDate(new DateTime($strDate, $timeZone));

        }
        
        return $event;
    }
    
    protected function shouldStripTags($element)
    {
        $strip_tags = true;
        switch ($element->name()) {
            case 'EXTRA_INFO':
                $strip_tags = false;
                break;
        }
        
        return $strip_tags;
    }
}

class CSTVAthleticEvent extends AthleticEvent
{
    protected $school;
    protected $opponent;
    protected $homeAway;
    protected $score;
    
    public function getTitle() {
        $title = '';
        switch ($this->homeAway)
        {
            case 'V':
                $title = sprintf("%s @ %s", $this->school, $this->opponent);
                break;
            case 'H':
                $title =  sprintf("%s @ %s", $this->opponent, $this->school);
            default:
                $title =  sprintf("%s vs. %s", $this->school, $this->opponent);
        }
        
        if ($this->score) {
            $title .= " ($this->score)";
        }
        
        return $title;
    }
    
    public function setSchool($school) {
        $this->school = $school;
    }
    
    public function getSchool() {
        return $this->school;
    }
    
    public function setOpponent($opponent) {
        $this->opponent = $opponent;
    }
    
    public function getOpponent() {
        return $this->opponent;
    }
    
    public function setHomeAway($home_away) {
        $this->homeAway = $home_away;
    }
    
    public function getHomeAway() {
        return $this->homeAway;
    }
    
    public function setScore($score) {
        $this->score = $score;
    }
    
    public function getScore() {
        return $this->score;
    }
}