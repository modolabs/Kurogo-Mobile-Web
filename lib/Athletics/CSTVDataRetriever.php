<?php

class CSTVDataRetriever extends URLDataRetriever
{
    protected $sport;
    
    protected $DEFAULT_PARSER_CLASS='CSTVDataParser';
    
    protected function initResponse() {
        $response = parent::initResponse();
        $response->setContext('sport', $this->sport);
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

    protected $items=array();

    protected static $startElements=array();
    protected static $endElements=array('EVENT');
    
    public function items()
    {
        return $this->items;
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
                $filters = array();
                if ($sport = $this->response->getContext('sport')) {
                    $filters['sport'] = $sport;
                }

                $event = $this->parseEntry($element);
                if ($event->filterItem($filters)) {
                    $this->items[] = $event;
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
        $event = new AthleticEvent();
        if ($id = $entry->getAttrib('ID')) {
            $event->setID($id);
        }
        $event->setSport($entry->getProperty('SPORT'));
        $event->setSportFullName($entry->getProperty('SPORT_FULLNAME'));
        $event->setOpponent($entry->getProperty('OPPONENT'));
        $event->setHomeAway($entry->getProperty('HOME_VISITOR'));
        $event->setLocation($entry->getProperty('LOCATION'));
        $event->setScore($entry->getProperty('OUTCOME_SCORE'));
        $event->setLinkToRecap($entry->getProperty('RECAP'));
        
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
                    $event->setTBA(true);
                    $time = '';
                    break;
            }
            
            $strDate = $time ? $sportDate . ' ' . $time : $sportDate;
            if ($timeZoneData = $this->transformTimeZone($curtimeZone)) {
                $timeZone = new DateTimeZone($timeZoneData);
            } else {
                $timeZone = Kurogo::siteTimezone();;
            }
            //save the event time to datetime object
            $event->setDateTime(new DateTime($strDate, $timeZone));
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