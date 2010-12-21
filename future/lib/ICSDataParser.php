<?php

require_once('ICalendar.php');

class ICSDataParser extends DataParser
{
    protected $eventClass='ICalEvent';
    protected function unfold($text) {
        return str_replace("\n ", "", $text);
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
        
    }
    
    protected function contentline($line) {
        $contentline = array(
            'name'=>'',
            'value'=>'',
            'params'=>array()
        );

        if (preg_match('/(.*?)(?!<\\\):(.*)/', $line, $parts)) {
            $params = explode(';', $parts[1]);
            $contentline['name'] = array_shift($params);
            $contentline['value'] = trim(ICalendar::ical_unescape_text($parts[2]));
            $contentline['params'] = array();
            foreach ($params as $param) {
                preg_match("/(.*?)=(.*)/", $param, $param_bits);
                $contentline['params'][$param_bits[1]] = str_replace("\"", "", $param_bits[2]);
            }
        } else {
        	error_log("Found a line $line that may not be valid");
        }
        
        return $contentline;
    }
    
    public function setEventClass($eventClass)
    {
    	if ($eventClass) {
    		if (!class_exists($eventClass)) {
                throw new Exception("Event class $eventClass not defined");
    		}
			$this->eventClass = $eventClass;
		}
    }
    
    public function parseData($contents)
    {
        $calendar = new ICalendar();
        $nesting = array();
        $contents = str_replace("\r\n", "\n", $contents);
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
                    $nesting[] = new $this->eventClass;

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
                    throw new ICalendarException('unknown component type ' . $value);
                    break;
                }
                break;
            case 'END':
                $last_object = array_pop($nesting);
                $last_obj_name = $last_object->get_name();
                if ($last_obj_name != $value) {
                    throw new ICalendarException("BEGIN $last_obj_name ended by END $value");
                }
                switch ($value) {
                case 'VEVENT':
                    if ($calendar->timezone) {
                        $last_object->set_attribute('TZID', $calendar->timezone->tzid);
                    }

                    $calendar->add_event($last_object);
                    break;
                case 'VTIMEZONE':
                    $calendar->timezone = $last_object;
                    break;
                case 'VCALENDAR':
                    break 3;
                }
                break;
            default:
                end($nesting)->set_attribute($contentname, $value, $params);
                break;
            }
        }
        
        return $calendar;
    }

}
