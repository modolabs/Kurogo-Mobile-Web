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
  * @subpackage People
  */
Kurogo::includePackage('People');

if (!function_exists('mb_convert_encoding')) {
    throw new KurogoException('Multibyte String PHP extension is not installed. http://www.php.net/manual/en/book.mbstring.php');
}

/**
  * @package Module
  * @subpackage People
  */
class PeopleWebModule extends WebModule {
    protected static $defaultModel = 'PeopleDataModel';
    protected $id = 'people';
    protected $detailFields = array();
    protected $detailAttributes = array();
    protected $encoding = 'UTF-8';
    protected $feeds=array();
    protected $feed;
    protected $contactGroups = array();
    protected $controllers = array();
    protected $defaultAllowRobots = false; // Require sites to intentionally turn this on

    protected function detailURLForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        return $this->buildBreadcrumbURL('detail', $params, true);
    }

    protected function getTitleForBookmark($aBookmark) {
        parse_str($aBookmark, $params);
        $titles = array($params['title']);
        if (isset($params['subtitle'])) {
            $titles[] = $params['subtitle'];
        }
        return $titles;
        
    }
  
    protected function formatValues($values, $info) {
        if (isset($info['parse'])) {
            $formatFunction = create_function('$value', $info['parse']);
            foreach ($values as &$value) {
                $value = $formatFunction($value);
            }
        }
        
        return $values;
    }
    
    protected function replaceFormat($format) {
        return str_replace(array('\n','\t'),array("\n","\t"), $format);
    }
  
    protected function formatDetail($values, $info, Person $person) {
        if (isset($info['format'])) {
            $value = vsprintf($this->replaceFormat($info['format']), $values);
        } else {
            $delimiter = isset($info['delimiter']) ? $info['delimiter'] : ' ';
            $value = implode($delimiter, $values);
        }
    
        $detail = array(
            'label' => isset($info['label']) ? $info['label'] : '',
            'title' => $value
        );
    
        switch(isset($info['type']) ? $info['type'] : 'text') 
        {
            case 'email':
                $detail['title'] = str_replace('@', '@&shy;', $detail['title']);
                $detail['url'] = "mailto:$value";
                $detail['class'] = 'email';
                break;
        
            case 'phone':
                $detail['title'] = str_replace('-', '-&shy;', $detail['title']);
                
                if (strpos($value, '+1') !== 0) { 
                    $value = "+1$value"; 
                }
                $detail['url'] = PhoneFormatter::getPhoneURL($value);
                $detail['class'] = 'phone';
                break;
 
            case 'imgdata':
                $detail['title'] = "";
                $detail['class'] = 'img';
                $detail['img'] = $this->buildURL('photo', array('id'=>$person->getID()));
                break;

            case 'imgurl':
                $detail['title'] = "";
                $detail['class'] = 'img';
                $detail['img'] = $value;
                break;

            // compatibility
            case 'map':
                $info['module'] = 'map';
                break;
        }

        if (isset($info['module'])) {
            $detail = array_merge($detail, Kurogo::moduleLinkForValue($info['module'], $value, $this, $person));
        }
        
        if (isset($info['urlfunc'])) {
            $urlFunction = create_function('$value,$person', $info['urlfunc']);
            $detail['url'] = $urlFunction($value, $person);
        }
    
        $detail['title'] = nl2br($detail['title']); 
        return $detail;
    }
  
    protected function formatPersonDetail(Person $person, $info, $key=0) {
        $section = array();
        
        if (count($info['attributes']) == 1) {
            $values = (array)$person->getField($info['attributes'][0]);
            if (count($values)) {
                $section[$key] = $this->formatDetail($this->formatValues($values, $info), $info, $person);
            }      
        } else {
            $valueGroups = array();
        
            foreach ($info['attributes'] as $attribute) {
                $values = $this->formatValues((array)$person->getField($attribute), $info);
            
                if (count($values)) {
                    foreach ($values as $i => $value) {
                        $valueGroups[$i][] = $value;
                    }
                } elseif (isset($info['format'])) {
                    //ensure that there is a value when format is set
                    $valueGroups[0][] = '';
                }
            }
          
            foreach ($valueGroups as $valueGroup) {
                $section[$key] = $this->formatDetail($valueGroup, $info, $person);
            }
        }
        
        return $section;
    }
  
    protected function formatPersonDetails(Person $person) {
        //error_log(print_r($this->detailFields, true));
        
        $details = array();    
        
        foreach($this->detailFields as $key => $info) {
            $section = $this->formatPersonDetail($person, $info, $key);
            
            if (count($section)) {
                if (isset($info['section'])) {
                    if (!isset($details[$info['section']])) {
                        $details[$info['section']] = $section;
                    } else {
                        $details[$info['section']] = array_merge($details[$info['section']], $section);
                    }
                } else {
                    $details[$key] = $section;
                }
            }
        }
        //error_log(print_r($details, true));
        return $details;
    }

    protected function htmlEncodeString($string) {
        return mb_convert_encoding($string, 'HTML-ENTITIES', $this->encoding);
    }
    
    public function searchItems($searchTerms, $limit=null, $options=null) {
        $feed = $this->feed ? $this->feed : $this->getDefaultFeed();
        //$feed = isset($options['feed']) ? $options['feed'] : 'people';
        $PeopleController = $this->getFeed($feed);
        $people = $PeopleController->search($searchTerms);
        return $people;
    }
    
    protected function getFeed($index) {
        if (isset($this->controllers[$index])) {
            return $this->controllers[$index];
        }
        
        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            
            $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
            $controller = PeopleDataModel::factory($modelClass, $feedData);
            $controller->setAttributes($this->detailAttributes);
            $this->controllers[$index] = $controller;
            return $controller;
        } else {
            throw new KurogoConfigurationException("Error getting people feed for index $index");
        }
    }

    protected function getDefaultFeed() {
        if ($this->feeds) {
            return current(array_keys($this->feeds));
        }
        return '';
    }
    
    protected function getSearchFeeds() {
        $feeds = array();
        foreach ($this->feeds as $feed => $feedData) {
            $feeds[$feed] = Kurogo::arrayVal($feedData,'TITLE', $feed);
        }
        return $feeds;
    }
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
    }

    protected function loadDetailAttributes($feed){
        if($this->feeds){
            if(count($this->feeds) == 1){
                # Load detail fields from page-detail.ini
                $this->detailFields = $this->loadPageConfigArea('detail', 'detailFields');
            }else{
                # Load detail fields from page-detail-[feed].ini
                $detailConfig = "detail-$feed";
                $this->detailFields = $this->loadPageConfigArea($detailConfig, 'detailFields');
            }
            foreach($this->detailFields as $field => $info) {
                $this->detailAttributes = array_merge($this->detailAttributes, $info['attributes']);
            }
            $this->detailAttributes = array_values(array_unique($this->detailAttributes));
        }
    }
    
    public function linkforItem(KurogoObject $person, $options=null) {    
        $title = $person->getName() ? $this->htmlEncodeString($person->getName()) : $this->getLocalizedString('NO_HEADER_TITLE');
        return array(
            'title'=>$title,
            'url'  =>$this->buildBreadcrumbURL('detail', array(
                                            'id'    => $person->getId(),
                                            'filter' => self::argVal($options,'filter'),
                                            'feed'   => $this->feed
                    ))
        );
    }

    public function linkForValue($value, Module $callingModule, $otherValue=null) {
        return array_merge(
            parent::linkForValue($value, $callingModule, $otherValue), 
            array('class' => 'action people'));
    }
    
    protected function getContactGroup($group) {
        if (!$this->contactGroups) {
            $this->contactGroups = $this->getModuleSections('contacts-groups');
        }
        
        if (isset($this->contactGroups[$group])) {
            if (!isset($this->contactGroups[$group]['contacts'])) {
                $this->contactGroups[$group]['contacts'] = $this->getModuleSections('contacts-' . $group);
            }

            if (!isset($this->contactGroups[$group]['description'])) {
                $this->contactGroups[$group]['description'] = '';
            }
            
            return $this->contactGroups[$group];            
        } else {
            throw new KurogoConfigurationException("Unable to find contact group information for $group");
        }
    }
    
    protected function getContacts() {
        //try version -1.1 page-index version first for backwards compatibility
        try { 
            $contacts = $this->loadPageConfigArea('index', 'contacts');
        } catch (KurogoConfigurationException $e) {
            $contacts = $this->getModuleSections('contacts');
        }
        
        foreach ($contacts as &$contact) {
            if (isset($contact['group']) && strlen($contact['group'])) {
                $group = $this->getContactGroup($contact['group']);
                if (!isset($contact['title']) && isset($group['title'])) {
                    $contact['title'] = $group['title'];
                }
                $contact['url'] = $this->buildBreadcrumbURL('group', array('group'=>$contact['group']));
            }
        }
        
        return $contacts;
    }
    
    protected function initializeForPage() {
        $this->feed = $this->getArg('feed', $this->getDefaultFeed());
        $this->loadDetailAttributes($this->feed);
        $PeopleController = $this->getFeed($this->feed);
        
        $this->assign('selectedFeed', $this->feed);

        switch ($this->page) {
        
            case 'detail':
                if ($uid = $this->getArg(array('id', 'uid'))) {
                    $person = $PeopleController->getUser($uid);
          
                    if ($person) {
                        $this->setLogData($uid, $person->getName());
                        $personDetails =  $this->formatPersonDetails($person);
                        // Bookmark
                        if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                            $cookieParams = array(
                                'title' => $person->getName(),
                                'uid' => rawurlencode($uid)
                            );
            
                            $cookieID = http_build_query($cookieParams);
                            $this->generateBookmarkOptions($cookieID);
                        }
                        
                        $headerSectionKeys = array('HEADER_THUMBNAIL', 'HEADER_TITLE', 'HEADER_SUBTITLE');
                        $headerSections = array();
                        foreach ($headerSectionKeys as $section) {
                            if (isset($personDetails[$section])) {
                                $headerSections[$section] = $personDetails[$section];
                                unset($personDetails[$section]);
                            }
                        }
                        $this->assign('headerSections', $headerSections);
                        $this->assign('personDetails', $personDetails);
                        break;
                    } else {
                        $this->assign('searchError', $PeopleController->getResponseError());
                    }          
                } else {
                    $this->assign('searchError', 'No username specified');
                }
                break;
                
            case 'photo':
                if ($uid = $this->getArg(array('id', 'uid'))) {
                    if ($person = $PeopleController->getUser($uid)) {
                        if ($data = $person->getPhotoData()) {
                            header("Content-type: ".$person->getPhotoMIMEType());
                            echo $data;
                            exit(0);
                        }
                    }
                }
                
                header("HTTP/1.1 404 Not Found");
                exit(0);
                break;
        
            case 'search':
                if ($filter = trim($this->getArg(array('filter', 'q')))) {
                    $searchTerms = trim($filter);
                    
                    $this->assign('feeds', $this->getSearchFeeds());
                    $this->assign('searchTerms', $searchTerms);
          
                    $startIndex = $this->getArg('start', 0);
                    $limit = $this->getOptionalModuleVar('MAX_PER_PAGE', 20);
                    $PeopleController->setStart($startIndex);
                    $PeopleController->setLimit($limit);
          
                    $this->setLogData($searchTerms);
                    $people = $this->searchItems($searchTerms);
                    $this->assign('searchError', $PeopleController->getResponseError());

                    if ($people != false && count($people) > 0) {
                        $resultCount = count($people);
                        $totalItems = $PeopleController->getTotalItems();

                        switch ($totalItems) 
                        {
                            case 1:
                                $person = current($people);
                                $this->logView();
                                $this->redirectTo('detail', array(
                                    'id'=>$person->getId(),
                                    'total'=>1,
                                    'filter'=>$filter,
                                    'feed'=>$this->feed
                                    )
                                );
                                break;
                            
                            default:
                                $results = array();
                                
                                $options = array('filter' => $filter);
                                foreach ($people as $person) {
                                    $results[] = $this->linkforItem($person, $options);
                                }
                                //error_log(print_r($results, true));
                                if($totalItems > $resultCount)
                                {
                                    if($startIndex + $limit < $totalItems)
                                    {
                                        $nextLink = $this->buildURL('search', array('feed' => $this->feed, 'filter' => $searchTerms, 'start' => $startIndex + $limit));
                                        $next = array('title' => $this->getLocalizedString("NEXT_PEOPLE_TEXT", $limit), 'url' => $nextLink, 'class' => 'pagerlink');
                                        array_push($results, $next);
                                    }
                                    if($startIndex > 0)
                                    {
                                        $prevLink = $this->buildURL('search', array('feed' => $this->feed, 'filter' => $searchTerms, 'start' => $startIndex - $limit));
                                        $prev = array('title' => $this->getLocalizedString("PREVIOUS_PEOPLE_TEXT", $limit), 'url' => $prevLink, 'class' => 'pagerlink');
                                        array_unshift($results, $prev);
                                    }
                                }
                                $this->assign('resultCount', $this->getFeed($this->feed)->getTotalItems());
                                $this->assign('results', $results);
                                break;
                        }
                      
                    } else {
                        $this->assign('searchError', $PeopleController->getResponseError());
                    }
                } else {
                  $this->redirectTo('index');
                }
                $this->assign('placeholder', $this->getLocalizedString("SEARCH"));
                break;

            case 'bookmarks':
                if (!$this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {            	
                    $this->redirectTo('index');
                }
                $bookmarks = array();

                foreach ($this->getBookmarks() as $aBookmark) {
                    if ($aBookmark) { // prevent counting empty string
                        $titles = $this->getTitleForBookmark($aBookmark);
                        $subtitle = count($titles) > 1 ? $titles[1] : null;
                        $bookmarks[] = array(
                                'title' => $titles[0],
                                'subtitle' => $subtitle,
                                'url' => $this->detailURLForBookmark($aBookmark),
                        );
                    }
                }
                $this->assign('bookmarks', $bookmarks);
                $this->assign('bookmarksTitle', $this->getLocalizedString('BOOKMARK_TITLE'));
                break;
                
            case 'group':
                $group = $this->getContactGroup($this->getArg('group'));
                if (isset($group['title'])) {
                    $this->setPageTitles($group['title']);
                }
                
                $this->assign('contacts', $group['contacts']);
                $this->assign('description', $group['description']);
                $this->assign('contactsSubTitleNewline', $this->getOptionalModuleVar('CONTACTS_SUBTITLE_NEWLINE', false));
                break;
        
            case 'index':
            case 'pane':
                $contacts = $this->getContacts();
                $this->assign('contacts', $contacts);
                
                $this->setAutoPhoneNumberDetection(false);
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->generateBookmarkLink();
                }
                $this->assign('feeds', $this->getSearchFeeds());
                $this->assign('placeholder', $this->getLocalizedString("SEARCH"));
                $this->assign('searchTip', $this->getOptionalModuleVar('SEARCH_TIP'));
                $this->assign('contactsSubTitleNewline', $this->getOptionalModuleVar('CONTACTS_SUBTITLE_NEWLINE', false));
                break;
        }  
    }
}
