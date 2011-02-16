<?php

class GoogleAppsCalendarListController extends CalendarListController
{
    protected $cacheLifetime = 300;
    
    public function getResources(User $user)
    {
        if (!$user instanceOf GoogleAppsUser) {
            return array();
        }
        
        $url = 'https://apps-apis.google.com/a/feeds/calendar/resource/2.0/' . $user->getDomain() .'/' ;
        $parameters = array(
            'alt'=>'json'
        );
        
        $data = $this->calendarQuery($url, $parameters, array(), $user, false);
        $data = json_decode($data, true);
        
        $feeds = array();

        if (isset($data['feed']['entry'])) {
            foreach ($data['feed']['entry'] as $resource) {
                $feed = array(
                    'CONTROLLER_CLASS'=>'GoogleAppsCalendarDataController',
                    'USER'=>$user
                );

                foreach ($resource['apps$property'] as $property) {
                    switch ($property['name'])
                    {
                        case 'resourceCommonName':
                            $feed['TITLE'] = $property['value'];
                            break;
                        case 'resourceDescription':
                            $feed['SUBTITLE'] = $property['value'];
                            break;
                        case 'resourceEmail':
                            // sadly the official feed isn't in the data. 
                            $feed['BASE_URL'] = sprintf("https://www.google.com/calendar/feeds/%s/private/full", $property['value']);
                    }
                }

                $feeds[$resource['id']['$t']] = $feed;
            }
        }
                
        return $feeds;
    }

    protected function calendarQuery($url, $parameters, $headers, User $user, $unique=false) {

        if (!$user instanceOf GoogleAppsUser) {
            return array();
        }
        
        $cache = new DiskCache(CACHE_DIR . "/" . 'GoogleCalendar', $this->cacheLifetime, TRUE);
        $cache->setSuffix('.json');
        $cache->preserveFormat();
        
        $cacheFilename = $unique ? md5($url . $user->getEmail()) : md5($url);
        
        if ($cache->isFresh($cacheFilename)) {
            $data = $cache->read($cacheFilename);
        } else {

            $authority = $user->getAuthenticationAuthority();
            $method = 'GET';        
        
            if ($data = $authority->oAuthRequest($method, $url, $parameters, $headers)) {
                $cache->write($data, $cacheFilename);
            }
        }
        
        return $data;
    }
    
    public function getUserCalendars(User $user) {
        if (!$user instanceOf GoogleAppsUser) {
            return array();
        }

        $url = 'https://www.google.com/calendar/feeds/default';
        $parameters = array(
            'alt'=>'jsonc'
        );
            
        $headers = array(
            'GData-Version: 2'
        );

        $data = $this->calendarQuery($url, $parameters, $headers, $user, true);
        $data = json_decode($data, true);

        $feeds = array();

        if (isset($data['data']['items'])) {
            foreach ($data['data']['items'] as $calendar) {
                $feeds[$calendar['id']] = array(
                    'CONTROLLER_CLASS'=>'GoogleAppsCalendarDataController',
                    'USER'=>$user, 
                    'BASE_URL'=>$calendar['eventFeedLink'],
                    'TITLE'=>$calendar['title']
                );
            }
        }

        return $feeds;
    }

}
