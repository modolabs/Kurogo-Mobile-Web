<?php

class  AthleticCalendar implements CalendarInterface {
    protected $events = array();
    protected  $timezone;
    
    public function setTimezone(CalendarTimeZone $timezone) {
        $this->timezone = $timezone;
    }

    public function getTimezone() {
        return $this->timezone;
    }

    public function addEvent(AthleticEvent $event) {
        $this->events[] = $event;
    }
    
    public function init($args) {
    }

    /* CalendarInterface */
    public function add_event(CalendarEvent $event) {
        $athleticEvent = new AthleticEvent();
        $athleticEvent->setID($event->getID());

        $athleticEvent->setTitle($event->get_summary());
        $athleticEvent->setDescription($event->get_description());
        $athleticEvent->setLocation($event->get_location());
        $athleticEvent->setStartDate(new DateTime("@" . $event->get_start()));
        
        $this->addEvent($athleticEvent);
    }

    public function set_attribute($contentname, $value, $params=null) {
        // no op
    }
  
    public function getEvents() {
        return $this->events;
    }
  
    public function getEventsInRange(TimeRange $range=null, $limit=null, $filters=null) {
        
        $events = array();
        $filters = is_array($filters) ? $filters : array();

        foreach ($this->events as $event) {
                    
            if ($event->filterItem($filters) && $range->overlaps($event->getRange())) {
                $events[] = $event;
            }
        }

        usort($events, array($this, "sort_events"));
        
        // in some case, it doesn't work properly if we just sort $this->eventStartTimes
        return $events;
    }

    private function sort_events(AthleticEvent $a, AthleticEvent $b) {
        $startA = $a->getStartTime();
        $startB = $b->getStartTime();
        if ($startA == $startB) {
            return 0;
        }
        return ($startA < $startB) ? -1 : 1;
    }
   
}

