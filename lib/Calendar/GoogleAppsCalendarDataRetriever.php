<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

class GoogleAppsCalendarDataRetriever extends OAuthDataRetriever implements SearchDataRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'GoogleCalendarDataParser';
    protected $authority;
    protected $requiresToken = true;
    
    protected function parameters() {
        $parameters = array_merge(parent::parameters(), array(
            'orderby'=>'starttime',
            'sortorder'=>'a',
            'singleevents'=>'true'
        ));
        
        if ($startDate = $this->getOption('startDate')) {
            $parameters['start-min'] = $startDate->format('c');
        }

        if ($endDate = $this->getOption('endDate')) {
            $parameters['start-max'] =  $endDate->format('c');
        }
        
        return $parameters;
    }
 
    public function search($searchTerms, &$response=null) {
        $this->addFilter('q', $searchTerms);
        if ($start = $this->getOption('start')) {
            $this->addFilter('start-index', $start+1);
        }
        
        if ($limit = $this->getOption('limit')) {
            $this->addFilter('max-results', $limit);
        }

        return $this->getData($response);
    }

    protected function addStandardFilters() {
        $this->addFilter('alt','jsonc');
        $this->addHeader('GData-Version', '2');
    }
    
    protected function init($args)
    {
        parent::init($args);
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


