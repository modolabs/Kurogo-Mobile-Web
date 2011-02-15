<?php

class GoogleAppsCalendarListController extends CalendarListController
{
    protected $cacheLifetime = 300;
    protected function getGoogleCalendars(User $user)
    {
        if (!$user instanceOf GoogleAppsUser) {
            return array();
        }
        
        $cache = new DiskCache(CACHE_DIR . "/" . 'GoogleCalendar', $this->cacheLifetime, TRUE);
        $cache->setSuffix('.json');
        $cache->preserveFormat();
        $cacheFilename = md5($user->getEmail());
        
        if ($cache->isFresh($cacheFilename)) {
            $data = $cache->read($cacheFilename);
        } else {

            $authority = $user->getAuthenticationAuthority();
    
            $method = 'GET';        
            $url = 'https://www.google.com/calendar/feeds/default';
    
            $parameters = array(
                'xoauth_requestor_id'=>$user->getEmail(),
                'alt'=>'jsonc'
            );
            
            $headers = array(
                'GData-Version: 2'
            );
        
            if ($data = $authority->oAuthRequest($method, $url, $parameters, $headers)) {
                $cache->write($data, $cacheFilename);
            }
        }
        
        $data = json_decode($data, true);
        $calendars = array();
        if (isset($data['data']['items'])) {
            foreach ($data['data']['items'] as $calendarData) {
                $calendars[] = $calendarData;
            }
        }
        
        return $calendars;
    }
    
    public function getUserCalendars(User $user) {
        $calendars = $this->getGoogleCalendars($user);
        $feeds = array();
        foreach ($calendars as $calendar) {
            $feeds[$calendar['id']] = array(
                'CONTROLLER_CLASS'=>'GoogleAppsCalendarDataController',
                'USER'=>$user, 
                'BASE_URL'=>$calendar['eventFeedLink'],
                'TITLE'=>$calendar['title']
            );
        }
        
        return $feeds;
    }
}
/*
class GoogleCalendar implements CalendarInterface
{
    protected $id;
    protected $title;
    protected $timeZone;
    protected $feed;
    
    public static function factory($args) {
        $calendar = new GoogleCalendar();
        $calendar->init($args);
        return $calendar;
    }
    
    public function getEventsInRange(TimeRange $range=null, $limit=null) {
        Debug::die_here($this);
    }
    
    public function init($args) {
        $this->title = $args['title'];
        $this->id = $args['id'];
        $this->timeZone = $args['timeZone'];
        $this->feed = $args['eventFeedLink'];
    }
    
    public function getID() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }
    
    public function getFeed() {
        return $this->feed;
    }
    
    public function getTimeZone() {
        return $this->timeZone;
    }
    
}
*/