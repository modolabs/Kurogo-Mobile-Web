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
  * ICalendar
  * The ICal* classes in this file together partially implement RFC 2445.
  * @package ExternalData
  * @subpackage Calendar
 */

/**
  * @package ExternalData
  * @subpackage Calendar
  */
class ICSDataParser extends DataParser
{
    protected $eventClass='ICalEvent';
    protected $calendarClass='ICalendar';
    protected function unfold($text) {
        return str_replace("\n ", "", $text);
    }
    
    protected function cleanContent($contents) {
        $contents = str_replace("\r\n", "\n", trim($contents));
        return $contents;
    }

    public function getEventCategories()
    {
        return call_user_func(array($this->eventClass, 'getEventCategories'));
    }
    
    public function init($args)
    {
        parent::init($args);

        if (isset($args['EVENT_CLASS'])) {
            $this->setEventClass($args['EVENT_CLASS']);
        }

        if (isset($args['CALENDAR_CLASS'])) {
            $this->setCalendarClass($args['CALENDAR_CLASS']);
        }
        
    }
    
    protected function contentline($line) {
        $contentline = array(
            'name'=>'',
            'value'=>'',
            'params'=>array()
        );

        if (preg_match('/([^":]*(?:"[^"]*"[^":]*)*):(.*)/', $line, $parts)) {
            $params = explode(';', $parts[1]);
            $contentline['name'] = array_shift($params);
            $contentline['value'] = trim(ICalendar::ical_unescape_text($parts[2]));
            $contentline['params'] = array();
            foreach ($params as $param) {
                if (preg_match("/(.*?)=(.*)/", $param, $param_bits)) {
                    $contentline['params'][$param_bits[1]] = str_replace("\"", "", $param_bits[2]);
                }
            }
        } else {
            Kurogo::log(LOG_WARNING, "Found an invalid ICS line: $line", 'data');
        }
        
        return $contentline;
    }
    
    public function setEventClass($eventClass)
    {
    	if ($eventClass) {
    		if (!class_exists($eventClass)) {
                throw new KurogoConfigurationException("Event class $eventClass not defined");
    		} 
			$this->eventClass = $eventClass;
		}
    }

    public function setCalendarClass($calendarClass)
    {
    	if ($calendarClass) {
    		if (!class_exists($calendarClass)) {
                throw new KurogoConfigurationException("Calendar class $calendarClass not defined");
    		} 
			$this->calendarClass = $calendarClass;
		}
    }
    
    protected function initEvent() {
        $event = new $this->eventClass();
        $event->init($this->initArgs);
        return $event;
    }

    protected function initCalendar() {
        $calendar = new $this->calendarClass();
        $calendar->init($this->initArgs);
        return $calendar;
    }
    
    public function parseData($contents)
    {
        $calendar = $this->initCalendar();
        if (!$calendar instanceOf CalendarInterface) {
            throw new KurogoConfigurationException("Calendar $class $this->calendarClass not a CalendarInterface");
        }
        $nesting = array();
        $nestingType = array();
        //all leading and trailing whitespace will be ignored
        $contents = $this->cleanContent($contents);
        $lines = explode("\n", $this->unfold($contents));
        foreach ($lines as $line) {
            $contentline = $this->contentline($line);
            $contentname = $contentline['name'];
            $value = $contentline['value'];
            $params = $contentline['params'];
            switch($contentname) {
            case 'BEGIN':
                switch ($value) {
                case 'VEVENT':
                    $addEvent = true;
                    $event = $this->initEvent();
                    if ($timezone = $calendar->getTimezone()) {
                        $event->set_attribute('TZID', $timezone->getTZID());
                    }
                    $nesting[] = $event;
                    break;
                case 'VCALENDAR':
                    $nesting[] = $calendar;
                    break;
                case 'VTIMEZONE':
                    $nesting[] = new ICalTimeZone();
                    break;
                case 'DAYLIGHT':
                    $nesting[] = new ICalDaylight();
                    break;
                case 'STANDARD':
                    $nesting[] = new ICalStandard();
                    break;
                case 'VTODO':
                    $nesting[] = new ICalTodo();
                    break;
                case 'VJOURNAL':
                    $nesting[] = new ICalJournal();
                    break;
                case 'VFREEBUSY':
                    $nesting[] = new ICalFreeBusy();
                    break;
                case 'VALARM':
                    $nesting[] = new ICalAlarm();
                    break;
                default:
                    if ($this->haltOnParseErrors) {
                        throw new ICalendarException('unknown component type ' . $value);
                    } else {
                        Kurogo::log(LOG_WARNING, "Unknown ICS type $value", 'data');
                    }
                    break;
                }
                if (count($nesting)==count($nestingType)+1) {
                    $nestingType[] = $value;
                }
                break;
            case 'END':
                $last_object = array_pop($nesting);
                $last_obj_name = array_pop($nestingType);
                if ($last_obj_name != $value) {
                    if ($this->haltOnParseErrors) {
                        throw new ICalendarException("BEGIN $last_obj_name ended by END $value");
                    } else {
                        Kurogo::log(LOG_WARNING, "BEGIN $last_obj_name ended by END $value", 'data');
                        $value = null; //throw it away
                    }
                }
                switch ($value) {
                    case 'VEVENT':
                        if ($addEvent) {
                            $calendar->add_event($last_object);
                        }
                        break;
                    case 'VTIMEZONE':
                        $calendar->setTimezone($last_object);
                        break;
                    case 'VCALENDAR':
                        break 3;
                }
                break;
            default:
                try {
                    if (empty($nesting))
                        throw new ICalendarException('Something other than BEGIN at the start of the calendar: '.$line);
                    
                    end($nesting)->set_attribute($contentname, $value, $params);
                } catch (ICalendarException $e) {
                    if ($this->haltOnParseErrors) {
                        throw $e;
                    }
                    Kurogo::log(LOG_WARNING, $e->getMessage(), 'data');
                    $addEvent = false;
                }
                break;
            }
        }

        $events = $calendar->getEvents();
        Kurogo::log(LOG_DEBUG, "Found ". count($events) . " events", 'data');
        $this->setTotalItems(count($events));
        return $calendar;
    }

}
