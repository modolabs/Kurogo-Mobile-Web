<?php

class GoogleAppsCalendarListController extends CalendarListController
{
    protected $cacheLifetime = 900;
    protected $authority;
    
    protected function getDomain() {
        return $this->authority ? $this->authority->getDomain() : false;
    }

    public function getResources() {
        $url = 'https://apps-apis.google.com/a/feeds/calendar/resource/2.0/' . $this->getDomain() .'/' ;
        $parameters = array(
            'alt'=>'json'
        );
        
        $data = $this->calendarQuery($url, $parameters, array(), false);
        $data = json_decode($data, true);
        
        $feeds = array();

        if (isset($data['feed']['entry'])) {
            $authority = $this->authority->getAuthorityIndex();

            foreach ($data['feed']['entry'] as $resource) {
                $feed = array(
                    'CONTROLLER_CLASS'=>'GoogleAppsCalendarDataController',
                    'AUTHORITY'=>$authority
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

    public function getUserCalendars() {

        $url = 'https://www.google.com/calendar/feeds/default';
        $parameters = array(
            'alt'=>'jsonc'
        );
            
        $headers = array(
            'GData-Version: 2'
        );

        $data = $this->calendarQuery($url, $parameters, $headers, true);
        $data = json_decode($data, true);

        $feeds = array();

        if (isset($data['data']['items'])) {
            $authority = $this->authority->getAuthorityIndex();
            foreach ($data['data']['items'] as $calendar) {
                $feeds[$calendar['id']] = array(
                    'CONTROLLER_CLASS'=>'GoogleAppsCalendarDataController',
                    'AUTHORITY'=>$authority,
                    'BASE_URL'=>$calendar['eventFeedLink'],
                    'TITLE'=>$calendar['title']
                );
            }
        }

        return $feeds;
    }

    protected function calendarQuery($url, $parameters, $headers=null, $unique=true) {
        $oauth = $this->oauth();
        if (!$token = $oauth->getToken()) {
            return false;
        }

        $cache = new DiskCache(CACHE_DIR . '/GoogleCalendar' . ($unique ? '/' . md5($token) :''), $this->cacheLifetime, TRUE);
        $cache->setSuffix('.cache');
        $cache->preserveFormat();
        
        $cacheURL = count($parameters) ? $url . '?' . http_build_query($parameters) : $url;
        
        $cacheFilename = md5($cacheURL);
        
        if ($cache->isFresh($cacheFilename)) {
            $response = unserialize($cache->read($cacheFilename));
            $data = $response->getResponse();
        } else {

            $oauth = $this->oauth();
            $method = 'GET';        
        
            if ($data = $oauth->oAuthRequest($method, $url, $parameters, $headers)) {
                $response = $oauth->getResponse();
                $cache->write(serialize($response), $cacheFilename);
            }
        }
        
        return $data;
    }
    
    protected function oauth() {
        return $this->authority->oauth();
    }
        
    protected function init($args) {
        parent::init($args);
        //either get the specified authority or attempt to get a GoogleApps authority
        $authorityIndex = isset($args['AUTHORITY']) ? $args['AUTHORITY'] : 'GoogleAppsAuthentication';
        $authority = AuthenticationAuthority::getAuthenticationAuthority($authorityIndex);
        
        //make sure we're getting a google apps authority
        if ($authority instanceOf GoogleAppsAuthentication) {
            $this->authority = $authority;
        }
    }

}
