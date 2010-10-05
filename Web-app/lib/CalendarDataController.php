<?php

class CalendarDataController extends DataController
{
	protected $start_date;
	protected $end_date;
	protected $calendar;
	protected $requires_date_filter=false;
	
	public function requires_date_filter($bool)
	{
		$this->requires_date_filter = $bool ? true : false;
	}

	protected function cacheFolder()
	{
		return CACHE_DIR . "/Calendar";
	}
	
	protected function cacheLifespan()
	{
		return $GLOBALS['siteConfig']->getVar('ICS_CACHE_LIFESPAN');
	}

	protected function cacheFileSuffix()
	{
		return '.ics';
	}
	
	public function setStartDate(DateTime $time)
	{
		$this->start_date = $time;
	}
	
	public function start_timestamp()
	{
		return $this->start_date ? $this->start_date->format('U') : false;
	}

	public function setEndDate(DateTime $time)
	{
		$this->end_date = $time;
	}

	public function end_timestamp()
	{
		return $this->end_date ? $this->end_date->format('U') : false;
	}

	public function setDuration($duration, $duration_units)
	{
		if (!$this->start_date) {
			return;
		} elseif (!preg_match("/^-?(\d+)$/", $duration)) {
			throw new Exception("Invalid duration $duration");
		}
		
		$this->end_date = clone($this->start_date);
		switch ($duration_units)
		{
			case 'year':
			case 'day':
			case 'month':
				$this->end_date->modify(sprintf("%s%s %s", $duration>=0 ? '+' : '', $duration, $duration_units));
				break;
			default:
				throw new Exception("Invalid duration unit $duration_units");
				break;
			
		}
	}

	public function getItem($id)
	{
		$items = $this->getItems();
		if (array_key_exists($id, $items)) {
			return $items[$id];
		}
		
		return false;
	}
	
	public function items() 
	{
		if (!$this->calendar) {
			$data = $this->getData();
			$this->calendar = $this->parseData($data);
		}

		$events = $this->calendar->get_events();
		if ($this->requires_date_filter) {
			$items = $events;
			$events = array();
			foreach ($items as $id => $event) {
				if  ((($event->get_start() >= $this->start_timestamp()) &&
						($event->get_start() <= $this->end_timestamp())) ||
		
					   (($event->get_end() >= $this->start_timestamp()) &&
						($event->get_end() <= $this->end_timestamp())) ||
		
						(($event->get_start() <= $this->start_timestamp()) &&
						($event->get_end() >= $this->end_timestamp()))) 
				{
					$events[$id] = $event;
				}
			}
		}
		
		return $events;
	}
}


?>