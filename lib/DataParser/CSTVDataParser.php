<?php
/**
  * @package ExternalData
  * @subpackage DataParser
  */

if (!function_exists('xml_parser_create')) {
    die('XML Parser commands not available.');
}

/**
  * @package ExternalData
  * @subpackage DataParser
  */
class CSTVDataParser extends XMLDataParser {
    protected $eventClass='AthleticEvent';
    protected $items=array();

    protected static $startElements=array('EVENT_INFO', 'EVENT');
    protected static $endElements=array('EVENT');
    
    public function items()
    {
        return $this->items;
    }

    protected function setEventClass($eventClass) {
        if ($eventClass) {
    		if (!class_exists($eventClass)) {
    			throw new KurogoConfigurationException("Cannot load class $eventClass");
    		}
			$this->eventClass = $eventClass;
		}
    }
    
    public function init($args) {
    
        if (isset($args['EVENT_CLASS'])) {
            $this->setEventClass($args['EVENT_CLASS']);
        }
    }

    protected function shouldHandleStartElement($name) {
        return in_array($name, self::$startElements);
    }

    protected function handleStartElement($name, $attribs) {
        switch ($name)
        {
            case 'EVENT_INFO':
                break;
            case 'EVENT':
                $this->elementStack[] = new $this->eventClass($attribs);
                break;
        }
    }

    protected function shouldHandleEndElement($name) {
        return in_array($name, self::$endElements);
    }

    protected function handleEndElement($name, $element, $parent) {
        switch ($name) {
            case 'EVENT':
                $element = $this->convertEventDateTime($element);
                $this->items[] = $element;
                break;
        }
    }
    
    protected function convertEventDateTime(AthleticEvent $event) {
        $strtime = '';
        if (!$date = $event->getProperty('event_date')) {
            return '';
        }
        $strtime = $date;
        
        if ($time = $event->getProperty('time')) {
            //need to check time format in feed (1:30 PM,All Day, TBA)
            switch ($time) {
                case 'All Day':
                    $event->setAllDay(true);
                    $time = '';
                    break;
                case 'TBA':
                    $event->setTBA(true);
                    $time = '';
                    break;
                default:
                    break;
            }
        } else {
            return '';
        }
        
        if ($time) {
            $strtime .= ' ' . $time;
        }
        //TODO not understand the timezone for United States(CT ET ..)
        /*
        if ($timeZoneData = $event->getProperty('time_zone')) {
            $timeZone = new DateTimeZone($timeZone);
        } else {
            $timeZone = Kurogo::siteTimezone();
        }
        */
        $timeZone = Kurogo::siteTimezone();
        $event->setDateTime(new DateTime($strtime, $timeZone));
        
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

