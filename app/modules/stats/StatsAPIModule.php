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
 * @package Module
 * @subpackage Stats
 */

class StatsAPIModule extends APIModule {

    protected $id = 'stats';
    protected $vmin = 1;
    protected $vmax = 1;

    protected function getServiceTypes() {
        return array(
            'web' => $this->getLocalizedString('SERVICE_WEB'), 
            'api' => $this->getLocalizedString('SERVICE_API')
        );
    }
    
    /* return interval types depends on pagetype */
    protected function getIntervalTypes() {
        $interval_types = array(
          'day' => array('duration' => 7, 'title' => $this->getLocalizedString('INTERVAL_DAY'), 'numdays' => 7),
          'week' => array('duration' => 12, 'title' => $this->getLocalizedString('INTERVAL_WEEK'), 'numdays' => 84),
          'month' => array('duration' => 12, 'title' => $this->getLocalizedString('INTERVAL_MONTH'), 'numdays' => 365),
          'custom' => array('title'=> $this->getLocalizedString('INTERVAL_CUSTOM'))
        );
        
        return $interval_types;
    }
    
    protected function initializeForCommand() {
        switch($this->command) {
            case 'get':

                $serviceTypes = $this->getServiceTypes();
                $service = $this->getArg('service', 'web');
                if (!array_key_exists($service, $serviceTypes)) {
                    throw new KurogoException("Invalid service type $service");
                }

                $options = array(
                    'service'=> $service
                );

                $interval_types = $this->getIntervalTypes();
                $interval = $this->getArg('interval', 'day');
                if ($interval) {
                    if (!array_key_exists($interval, $interval_types)) {
                        throw new KurogoException("Invalid interval $interval");
                    }
                }

                if ($interval == 'custom') {
                    $startTime = $this->getArg('start', mktime(0,0,0));
                    $endTime = $this->getArg('end', mktime(23,59,59));
                    if ($endTime < $startTime) {
                        $endTime = $startTime;
                    }
                } else {
                    $times = $this->getTimesForInterval($interval, $interval_types[$interval]['duration']);
                    $startTime = $times['start'];
                    $endTime = $times['end'];
                }
        
                $options['start'] = $startTime;
                $options['end'] = $endTime;
                
                foreach (array('sort','sort_dir','group','field','top') as $field) {
                    if ($val = $this->getArg($field)) {
                        $options[$field] = $val;
                    }
                }

                foreach (KurogoStats::validFields() as $field) {
                    $val = $this->getArg($field, null);
                    if (!is_null($val)) {
                        $options[$field] = $val;
                    }
                }

                $data = $this->getStatsData($options);
                
                $this->setResponse($data);
                $this->setResponseVersion(1);
                break;
            default:
                 $this->invalidCommand();
                 break;
        }
    }

    /* retrieves the stats data based on the array */
    protected function getStatsData($options) {
		$kurogoOption = new KurogoStatsOption();
		$type = isset($options['stattype']) ? $options['stattype'] : 'count';
        $kurogoOption->setType($type);

        if ($options['service']) {
            $kurogoOption->setService($options['service']);
            unset($options['service']);
        }

        if ($options['start']) {
            $kurogoOption->addFilter('timestamp', 'GT', $options['start']);
        }

        if ($options['end']) {
            $kurogoOption->addFilter('timestamp', 'LT', $options['end']);
        }

        if (isset($options['sort'])) {
            $kurogoOption->setSortField($options['sort']);
        }

        if (isset($options['sort_dir'])) {
            $kurogoOption->setSortDir($options['sort_dir']);
        }

        if (isset($options['group'])) {        
            $kurogoOption->setGroup($options['group']);
        }

        if (isset($options['field'])) {        
            $kurogoOption->setField($options['field']);
        }
        
        if (isset($options['top'])) {
            $kurogoOption->setLimit($options['top']);
        }

        foreach (KurogoStats::validFields() as $field) {
            if (isset($options[$field])) {
                $kurogoOption->addFilter($field, 'EQ', $options[$field]);
            }
        }

        return KurogoStats::retrieveStats($kurogoOption);
	}

    protected function getTimesForInterval($interval, $data=null) {
        switch ($interval)
        {
            case 'day':
                //includes today
                $time = mktime(0,0,0);
                break;
            case 'week':
                //includes this week
                $time = time();
                $time = $time - (86400 * (date('w', $time)));
                $time = mktime(0,0,0,date('m', $time),date('d', $time),date('Y', $time));
                break;
            case 'month':
                //includes this month
                $time = mktime(0,0,0,date('m'),1,date('Y'));
                break;
            case 'custom':
                return array(
                    'start'=>$data['start'],
                    'end'=>$data['end']
                );
                break;
            default:
                throw new exception("Invalid interval $interval");
                break;
        }

        $interval_types = $this->getIntervalTypes();
        $duration = $interval_types[$interval]['duration'];
        $startTime = strtotime("-". ($duration-1) . "{$interval}s", $time);
        $endTime = strtotime("+1 {$interval}", $time)-1;
        
        return array(
            'start'=>$startTime,
            'end'=>$endTime,
        );
        
    }
}
