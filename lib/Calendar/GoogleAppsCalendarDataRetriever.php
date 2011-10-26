<?php

class GoogleAppsCalendarDataRetriever extends URLDataRetriever
{
    protected $authority;
    protected $supportsSearch = true;
    
    protected function url() {
        $this->addFilter('orderby', 'starttime');
        $this->addFilter('sortorder', 'a');
        $this->addFilter('singleevents', 'true');
        
        if ($startDate = $this->dataController->getStartDate()) {
            $this->addFilter('start-min', $startDate->format('c'));
        }

        if ($endDate = $this->dataController->getEndDate()) {
            $this->addFilter('start-max', $endDate->format('c'));
        }

        return parent::url();
    }
    
    public function search($searchTerms) {
        $this->addFilter('q', $searchTerms);
        if ($start = $this->dataController->getStart()) {
            $this->addFilter('start-index', $start+1);
        }
        
        if ($limit = $this->dataController->getLimit()) {
            $this->addFilter('max-results', $limit);
        }

        return $this->retrieveData();
    }

    protected function cacheFolder() {
        $oauth = $this->oauth();
        $token = $oauth->getToken();
        return CACHE_DIR . "/" . $this->cacheFolder . ($token ? "/" . md5($token) : '');
    }
    
    public function retrieveData() {
        $url = $this->url();
    
        $oauth = $this->oauth();
        $parameters = array(); //set in query string
        $headers = $this->getHeaders();
        return $oauth->oauthRequest('GET', $url, $parameters, $headers);
    }
    
    protected function addStandardFilters() {
        $this->addFilter('alt','jsonc');
        $this->addHeader('GData-Version', '2');
    }
    
    protected function oauth() {
        return $this->authority->oauth();
    }
    
    protected function init($args)
    {
        parent::init($args);
        //either get the specified authority or attempt to get a GoogleApps authority
        $authorityIndex = isset($args['AUTHORITY']) ? $args['AUTHORITY'] : 'GoogleAppsAuthentication';
        $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex);
        
        //make sure we're getting a google apps authority
        if ($authority instanceOf GoogleAppsAuthentication) {
            $this->authority = $authority;
        }
        
        $this->addStandardFilters();
    }
}

class GoogleCalendarDataParser extends DataParser
{
    protected $eventClass='ICalEvent';

    public function setEventClass($eventClass)
    {
    	if ($eventClass) {
    		if (!class_exists($eventClass)) {
                throw new KurogoConfigurationException("Event class $eventClass not defined");
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
                throw new KurogoDataException("Need to handle multiple when values. Please report this as a bug including calendar and event used");
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


