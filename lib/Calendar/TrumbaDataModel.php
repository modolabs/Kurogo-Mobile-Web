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
  * @package ExternalData
  * @subpackage Calendar
  */

class TrumbaDataModel extends CalendarDataModel
{
    protected $DEFAULT_EVENT_CLASS='TrumbaEvent';
    protected $trumbaFilters=array();
    protected $supportsSearch = true;
    protected $categoryFilter = '';
    protected $categoryFilterView = '';
    
    protected function addTrumbaFilter($var, $value) {
        $this->trumbaFilters[$var] = $value;
        $index = count($this->trumbaFilters);
        $this->addFilter('filter'.$index, $value);
        $this->addFilter('filterfield'.$index, $var);
    }
    
    public function addFilter($var, $value) {
        switch ($var) {
            case 'category':
                if ($this->categoryFilter) {
                    // special trumba category filters
                    $this->addTrumbaFilter($this->categoryFilter, $value);
                    break;
                }
                // fallthrough
                
            default:
                parent::addFilter($var, $value);
                break;
        }
    }

    public function getEventsByCategory($cateID) {
        if ($this->categoryFilter) {
            $this->setLimit(null);
            $this->addFilter('category', $cateID);
            return $this->items();
        } else {
            return parent::getEventsByCategory($cateID);
        }
    }

    protected function getCalendar() {
        if (empty($this->startDate)) {
            throw new KurogoConfigurationException('Start date cannot be blank');
        }
        
        if (empty($this->endDate)) {
            $endDate = clone $this->startDate;
            $endDate->modify('+30 days');
            $this->setEndDate($endDate);
        }
        
        // Trumba requires dates when doing category searches
        $days = intval($this->endDate->format('d')) - intval($this->startDate->format('d'));
        $months = intval($this->endDate->format('m')) - intval($this->startDate->format('m'));
        $years = intval($this->endDate->format('Y')) - intval($this->startDate->format('Y'));
        if ($years > 0) {
            $months += $years * 12;
        }
        
        if ($months > 0) {
            $months++; // include next month
        
            $this->addFilter('startdate', $this->startDate->format('Ym').'01');
            $this->addFilter('months', $months);
            
        } else {
            $days++; // include next day

            $this->addFilter('startdate', $this->startDate->format('Ymd'));
            $this->addFilter('days', $days);
        }
        
        if ($this->categoryFilterView) {
            // Include filterview parameter if configured
            $this->addFilter('filterview', $this->categoryFilterView);
        }
        
        return parent::getCalendar();
    }

    public function getItem($id, $time=null) {
        if ($time === null) {
            throw new KurogoConfigurationException("Can't load a Trumba event without a time");
        }
        
        // Trumba start time must be at midnight
        $start = new DateTime(date('Y-m-d H:i:s', $time));
        $start->setTime(0,0,0);
        $time = $start->format('U');
        
        return parent::getItem($id, $time);
    }

    protected function init($args) {
        // Pass default onto parser/retriever
        if (!isset($args['EVENT_CLASS'])) {
            $args['EVENT_CLASS'] = $this->DEFAULT_EVENT_CLASS;
        }

        parent::init($args);
        
        if (isset($args['CATEGORY_FILTER_FIELD'])) {
            $this->categoryFilter = $args['CATEGORY_FILTER_FIELD'];
        }
        
        if (isset($args['CATEGORY_FILTER_VIEW'])) {
            $this->categoryFilterView = $args['CATEGORY_FILTER_VIEW'];
        }
    }
}

/**
  * @package ExternalData
  * @subpackage Calendar
  */
class TrumbaEvent extends ICalEvent
{
    protected $trumbaCustomFields = array();
    
    public function set_attribute($attr, $value, $params=NULL) {
        switch ($attr) {
            case 'X-TRUMBA-CUSTOMFIELD':
                if (array_key_exists('NAME', $params)) {
                    $name = $params['NAME'];
                    unset($params['NAME']);
                    $this->trumbaCustomFields[$name] = $value;
                    $this->set_attribute($name, $value, $params);
                }
                break;
            default:
                parent::set_attribute($attr, $value, $params);
                break;
        }
    }
}
