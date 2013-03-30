<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

includePackage('Authentication');
class GoogleAppsCalendarListRetriever extends OAuthDataRetriever implements CalendarListRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'GoogleAppsCalendarListParser';
    protected $cacheLifetime = 900;
    protected $requiresToken = true;
    
    protected function getDomain() {
        return $this->authority ? $this->authority->getDomain() : false;
    }
    
    public function parameters() {
        $parameters = parent::parameters();
        switch ($this->getOption('action')) {
            case 'userCalendars':
                $parameters['alt'] = 'jsonc';
                break;
            case 'resources':
                $parameters['alt'] = 'json';
                break;
            default:
                throw new KurogoException("Unknown action " . $this->getOption('action'));
        }
        
        return $parameters;
    }
    
    public function baseURL() {
        switch ($this->getOption('action')) {
            case 'userCalendars':
                $url = 'https://www.google.com/calendar/feeds/default';
                break;
            case 'resources':
                $url = 'https://apps-apis.google.com/a/feeds/calendar/resource/2.0/' . $this->getDomain() .'/';
                break;
            default:
                throw new KurogoException("Unknown action " . $this->getOption('action'));
        }
        
        return $url;
    }
    
    public function init($args) {
        parent::init($args);
        if (!$this->authority) {
            if ($authority = AuthenticationAuthority::getAuthenticationAuthority('GoogleAppsAuthentication')) {
                $this->setAuthority($authority);
            }
        }
    }
}

class GoogleAppsCalendarListParser extends DataParser
{
    protected $authority;
    private function parseCalendarFeeds($data) {
        $feeds = array();
        
        $baseFeed = $this->getBaseFeed();
        
        if (isset($data['data']['items'])) {
            foreach ($data['data']['items'] as $calendar) {
                $feeds[$calendar['id']] = array_merge($baseFeed, array(
                    'BASE_URL'=>$calendar['eventFeedLink'],
                    'TITLE'=>$calendar['title']
                ));
            }
        }
        
        return $feeds;
    }
    
    private function getBaseFeed() {
    	$baseFeed = $this->initArgs;
        unset($baseFeed['PARSER_CLASS']);
        $baseFeed['RETRIEVER_CLASS']='GoogleAppsCalendarDataRetriever';
        
        return $baseFeed;
    }
    
    private function parseResourceFeeds($data) {
        $feeds = array();

        $baseFeed = $this->getBaseFeed();

        if (isset($data['feed']['entry'])) {

            foreach ($data['feed']['entry'] as $resource) {

                $feed = $baseFeed;
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

    public function parseResponse(DataResponse $response) {
        if ($authority = $response->getContext('authority')) {
            $this->authority = $authority;
        }
        
        return parent::parseResponse($response);
    }
    
    public function parseData($data) {
        $data = json_decode($data, true);
        
        if (isset($data['data']['kind']['calendar#calendarFeed'])) {
            return $this->parseCalendarFeeds($data);
        } elseif (isset($data['feed']['entry'])) {
            return $this->parseResourceFeeds($data);
        } 
        
        Kurogo::log(LOG_WARNING, 'Unknown data found', 'data');
        return array();
    }
}
