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

class StatsWebModule extends WebModule {
	  protected $id = 'stats';
    protected $defaultAllowRobots = false; // Require sites to intentionally turn this on

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
    
	protected function initializeForPage() {
	    if (!Kurogo::getOptionalSiteVar('STATS_ENABLED', true)) {
	        throw new KurogoException($this->getLocalizedString('STATS_DISABLED'));
	    }

        if ($this->page == 'updateStats'){
            KurogoStats::exportStatsData();
            $this->redirectTo('index', array());
        }
        
        if ($this->getOptionalModuleVar('AUTO_UPDATE_STATS')) {
            KurogoStats::exportStatsData();
        }

        $this->initChart();	
	    $serviceTypes = $this->getServiceTypes();
	    $service = $this->getArg('service', 'web');
	    if (!array_key_exists($service, $serviceTypes)) {
	        $args = $this->args;
	        $args['service'] = 'web';
	        $this->redirectTo($this->page, $args);
	    }

        $interval_types = $this->getIntervalTypes();
        $interval = $this->getArg('interval', 'day');
        if (!array_key_exists($interval, $interval_types)) {
	        $args = $this->args;
	        $args['interval'] = 'day';
	        $this->redirectTo($this->page, $args);
	    }

        if ($interval == 'custom') {
            $start = $this->getArg('start');
            $startTime = $start ? mktime(0,0,0, $start['Month'], $start['Day'], $start['Year']) : mktime(0,0,0);
            $end = $this->getArg('end', array());
            $endTime = $end ? mktime(23,59,59, $end['Month'], $end['Day'], $end['Year']) : mktime(23,59,59);
            if ($endTime < $startTime) {
                $endTime = $startTime;
            }
        } else {
            $times = $this->getTimesForInterval($interval, $interval_types[$interval]['duration']);
            $startTime = $times['start'];
            $endTime = $times['end'];
        }
        
        $intervalOptions = array();
        $args = $this->args;
        $args['service'] = $service;
        $args['interval'] = $interval;
        
        foreach ($interval_types as $interval_type=>$type) {
            $args['interval'] = $interval_type;
            $intervalOptions[$interval_type] = array(
                'title'=>$type['title'],
                'selected'=>$interval_type == $interval,
                'url'=>$this->buildbreadcrumbURL($this->page, $args, false)
            );
        }

        $args = $this->args;
        $args['service'] = $service;
        $args['interval'] = $interval;
        foreach ($serviceTypes as $serviceType=>$serviceTypeTitle) {
            $args['service'] = $serviceType;
            $serviceOptions[$serviceType] = array(
                'title'=>$serviceTypeTitle,
                'selected'=>$service == $serviceType,
                'url'=>$this->buildbreadcrumbURL($this->page, $args, false)
            );
        }

        $this->assign('starttime', $startTime);
        $this->assign('endtime', $endTime);
        $this->assign('statsService', $service);
        $this->assign('interval', $interval);
        $this->assign('intervalOptions', $intervalOptions);
        $this->assign('serviceOptions', $serviceOptions);
        $this->assign('intervalTabclass', count($interval_types)==4 ? 'fourtabs': 'threetabs');
        $this->assign('serviceTabclass', 'twotabs');

        $commonData = array(
            'service'=> $service,
            'start'=>$startTime,
            'end'=>$endTime
        );

		if ($date = KurogoStats::getLastDateFromSummary()){
			includePackage('DateTime');
			$date = new DateTime($date);
			$this->assign('lastUpdated', DateFormatter::formatDate($date, DateFormatter::LONG_STYLE, DateFormatter::NO_STYLE));
		}
		        
		switch ($this->page) {
			case 'index':

			    //get config
			    $chartsConfig = $this->getModuleSections('stats-index');
			        
			    $charts = array();
			    foreach ($chartsConfig as $chartIndex=>$chartData) {
                    try {
			            $charts[] = $this->prepareChart(array_merge($chartData, $commonData), $interval);
                    } catch (KurogoStatsConfigurationException $e) {
                        $this->redirectTo('statsconfigerror', array('chart' => $chartData['title']));
                    }
			    }

                $this->assign('charts', $charts);
                break;
                
            case 'detail':
                if (!$group = $this->getArg('group')) {
                    $this->redirectTo('index', array());
                }
                
                if (!in_array($group, array('moduleID','platform','pagetype'))) {
                    $this->redirectTo('index', array());
                }
                
                
                if (!$$group = $this->getArg($group)) {
                    $this->redirectTo('index', array());
                }
                
                switch ($group)
                {
                    case 'moduleID':
                        
                        break;
                    case 'platform':
                        break;
                    case 'pagetype':
                        break;
                }
                
                if (!$chartsConfig = $this->getChartsConfig($group, $$group)) {
                    $this->redirectTo('index', array());
                }

			    $charts = array();
			    $commonData[$group] = $$group;
			    foreach ($chartsConfig as $chartIndex=>$chartData) {
                    try {
                        $charts[] = $this->prepareChart(array_merge($chartData, $commonData), $interval);    
                    } catch (KurogoStatsConfigurationException $e) {
                        $this->redirectTo('statsconfigerror', array('chart' => $chartData['title']));
                    }
			        
			    }

                $this->setPageTitle(sprintf("Stats for %s", $$group));
                $this->assign('charts', $charts);
                break;
            case 'statsconfigerror':
                $this->assign('chart', $this->getArg('chart'));
                break;
		}
	}
	
	protected function prepareChart($chartData, $interval) {
        if (isset($chartData['interval'])) {
            
            $intervals = $this->getIntervalTimesForInterval($interval, $chartData);
            $data = array();
            foreach ($intervals as $intervalData) {
                $data[$intervalData['title']] = $this->getStatsData(
                    array_merge($chartData, $intervalData)
                );
            }
                        
        } else {
            $data = $this->getStatsData($chartData);
        }
        
        if (isset($chartData['group']) && $chartData['group']=='data') {
            $labels = array();
            $values = array();
            foreach ($data as $k=>$v) {
                $labels[$k] = $v['label'];
                $values[$k] = $v['count'];
            }
            $data = $values;
            $chartData['labels'] = $labels;
        }
        
        $chartData['data'] = $data;
        
        if (isset($chartData['detailurl']) && strlen($chartData['detailurl'])) {
            $chartData['URL'] = array();
            foreach ($chartData['data'] as $key=>$val) {
                $chartData['URL'][$key] = $this->buildbreadcrumbURL($chartData['detailurl'], 
                    array_merge($this->args, array('group'=>$chartData['group'],$chartData['group']=>$key)));
            }
        }

        return $chartData;
    }
        
    /* retrieves the stats data based on the array */
    protected function getStatsData($chartData) {
		$kurogoOption = new KurogoStatsOption();
		$type = isset($chartData['stattype']) ? $chartData['stattype'] : 'count';
        $kurogoOption->setType($type);

        if ($chartData['service']) {
            $kurogoOption->setService($chartData['service']);
            unset($chartData['service']);
        }

        if ($chartData['start']) {
            $kurogoOption->addFilter('timestamp', 'GTE', $chartData['start']);
        }

        if ($chartData['end']) {
            $kurogoOption->addFilter('timestamp', 'LTE', $chartData['end']);
        }

        if (isset($chartData['sort'])) {
            $kurogoOption->setSortField($chartData['sort']);
        }

        if (isset($chartData['sort_dir'])) {
            $kurogoOption->setSortDir($chartData['sort_dir']);
        }

        if (isset($chartData['group'])) {        
            $kurogoOption->setGroup($chartData['group']);
        }

        if (isset($chartData['field'])) {        
            $kurogoOption->setField($chartData['field']);
        }
        
        if (isset($chartData['top'])) {
            $kurogoOption->setLimit($chartData['top']);
        }

        foreach (KurogoStats::validFields() as $field) {
            if (isset($chartData[$field])) {
                $kurogoOption->addFilter($field, 'EQ', $chartData[$field]);
            }
        }

        return KurogoStats::retrieveStats($kurogoOption, $chartData);
	}

    protected function getIntervalTimesForInterval($interval, $data=null) {
        $times = $this->getTimesForInterval($interval, $data);
        $intervals = array();
        $interval_types = $this->getIntervalTypes();
        $step = 1;
        
        switch ($interval)
        {
            case 'day':
            case 'week':
                $duration = $interval_types[$interval]['duration'];
                $format = '%m/%d';
                break;
            case 'month':
                $duration = $interval_types[$interval]['duration'];
                $format = '%h %Y';
                break;
            case 'custom':
                $diff = $times['end'] - $times['start'];
                $interval = 'day';
                $format = '%m/%d';
                if ($diff < 90001) { //one day (use 25 hours+1 second to avoid DST issues)
                    $duration = 1;
                } elseif ($diff < 864000) { // 10 days
                    $duration = ceil($diff / 86400);
                } else {
                    $step = $diff / 10;
                    $time = $times['start'];
                    while ($time < $times['end']) {
                        $next = $time + $step;
                        $next = mktime(23,59,59, date('m', $next), date('d', $next), date('Y', $next));
                        if ($next > $times['end']) {
                            $next = $times['end'];
                        }
                        $intervals[] = array(
                            'start'=>$time,
                            'end'=>$next,
                            'title'=>strftime("%m/%d",$time).'-'.strftime('%m/%d', $next)
                        );
                        $time = $next+1;
                    }
                    return $intervals;
                }
                break;
            default:            
                throw new exception("Invalid interval $interval");
                break;
        }
        
        $time = $times['start'];
        for ($i=0; $i<$duration; $i++) {
            $next = strtotime("+$step $interval", $time);
            $intervals[] = array(
                'start'=>$time,
                'end'=>$next-1,
                'title'=>strftime($format, $time)
            );
            $time = $next;
        }

        return $intervals;
        
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
    
    protected function getChartsConfig($group, $groupValue) {
    
        switch ($group)
        {
            case 'moduleID':
                $chartsConfig = $this->getModuleSections('stats-module-detail');
                try {
                    $module = Webmodule::factory($groupValue);
                    $moduleChartsConfig = $module->getModuleSections('stats-detail');
                    
                } catch (KurogoModuleNotFound $e) {
                    return false;
                } catch (Exception $e) {
                    $moduleChartsConfig = array();
                }
                $chartsConfig = array_merge($chartsConfig, $moduleChartsConfig);
                break;
            case 'platform':
                $platforms = KurogoStats::$platforms;
                if (!array_key_exists($groupValue, $platforms)) {
                    return false;
                }
                $chartsConfig = $this->getModuleSections("stats-platform-detail");
                
                break;
            case 'pagetype':
                $pagetypes = KurogoStats::$pagetypes;
                if (!array_key_exists($groupValue, $pagetypes)) {
                    return false;
                }
                $chartsConfig = $this->getModuleSections("stats-pagetype-detail");
                break;
            default:
                throw new Exception("No config for group $group $groupValue");
        }
        
        return $chartsConfig;
    }

    public static function formatBytes($value) {
		//needs integer
		if (!preg_match('/^\d+$/', $value)) {
			return $value;
		}
		
		//less than 10,000 bytes return bytes
		if ($value < 10000) {
			return $value;
		//less than 1,000,000 bytes return KB
		} elseif ($value < 1000000) {
			return sprintf("%.2f KB", $value/1024);
		} elseif ($value < 1000000000) {
			return sprintf("%.2f MB", $value/(1048576));
		} elseif ($value < 1000000000000) {
			return sprintf("%.2f GB", $value/(1073741824));
		} else {
			return sprintf("%.2f TB", $value/(1099511627776));
		}
	}
}
