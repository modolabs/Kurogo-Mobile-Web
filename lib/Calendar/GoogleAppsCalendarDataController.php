<?php

class GoogleAppsCalendarDataController extends CalendarDataController
{
    protected $DEFAULT_PARSER_CLASS='GoogleCalendarDataParser';
    protected $cacheFolder = 'GoogleCalendar';
    protected $cacheFileSuffix = 'json';
    protected $user;
    protected $authority;
    
    public function setUser(User $user) {
        if ($user instanceOf GoogleAppsUser) {
            $this->user = $user;
            $this->authority = $user->getAuthenticationAuthority();
            return true;
        }
    }

    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search':
                return parent::addFilter('q', $value);
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    protected function url() {
        $this->addFilter('orderby', 'starttime');
        $this->addFilter('sortorder', 'a');
        $this->addFilter('singleevents', 'true');

        if ($this->startDate) {
            $this->addFilter('start-min', $this->startDate->format('c'));
        }

        if ($this->endDate) {
            $this->addFilter('start-max', $this->endDate->format('c'));
        }

        return parent::url();
    }
    
    
    protected function retrieveData($url) {
        if (!$this->authority) {
            throw new Exception("Authority not set");
        }
        
        $parameters = array(
            'xoauth_requestor_id'=>$this->user->getEmail(),
            'alt'=>'jsonc'
        );
        
        $headers = array(
            'GData-Version: 2'
        );

        $result = $this->authority->oauthRequest('GET', $url, $parameters, $headers);
        return $result;
    }
    
    protected function init($args)
    {
        parent::init($args);
        if (isset($args['USER'])) {
            $this->setUser($args['USER']);
        }
    }
}

class GoogleCalendarDataParser extends DataParser
{
    protected $eventClass='ICalEvent';

    public function setEventClass($eventClass)
    {
    	if ($eventClass) {
    		if (!class_exists($eventClass)) {
                throw new Exception("Event class $eventClass not defined");
    		}
			$this->eventClass = $eventClass;
		}
    }

    public function init($args)
    {
        parent::init($args);

        if (isset($args['EVENT_CLASS'])) {
            $this->setEventClass($args['EVENT_CLASS']);
        }
        
    }

    public function parseData($data)
    {
        $calendar = new ICalendar();

        $data = json_decode($data, true);
        $items = isset($data['data']['items']) ? $data['data']['items'] : array();
        $total = 0;

        foreach ($items as $item) {
            if (!isset($item['when'])) {
                //probably an orphaned event. 
                continue;
            }
            $event = new $this->eventClass();
            $event->setUID($item['id']);
            $event->setSummary($item['title']);
            $event->setDescription($item['details']);
            if (isset($item['location'])) {
                $event->setLocation($item['location']);
            }
            if (count($item['when'])>1) {
                throw new Exception("Need to handle multiple when values. Please report this as a bug including calendar and event used");
            }

            $start = new DateTime($item['when'][0]['start']);
            $end = new DateTime($item['when'][0]['end']);
            if (stripos($item['when'][0]['start'], 'T')!== false) {
                $range = new TimeRange($start->format('U'), $end->format('U'));
            } else {
                //make all day events last until 11:59 of the end day
                $range = new DayRange($start->format('U'), $end->format('U')-1);
            }
            
            $event->setRange($range);
            $calendar->add_event($event);
            $total++;
        }
        
        $this->setTotalItems($total);

        return $calendar;
    }
    
}


