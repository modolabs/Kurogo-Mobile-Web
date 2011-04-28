<?php
/**
  * @package Module
  * @subpackage People
  */
includePackage('People');


/**
  * @package Module
  * @subpackage People
  */
class PeopleWebModule extends WebModule {
    protected $id = 'people';
    protected $bookmarkLinkTitle = 'Bookmarked People';
    private $detailFields = array();
    private $detailAttributes = array();
    protected $defaultController = 'LDAPPeopleController';
    protected $feeds=array();

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
  
    private function formatValues($values, $info) {
        if (isset($info['parse'])) {
            $formatFunction = create_function('$value', $info['parse']);
            foreach ($values as &$value) {
                $value = $formatFunction($value);
            }
        }
        
        return $values;
    }
  
    private function formatDetail($values, $info, Person $person) {
        if (isset($info['format'])) {
            $value = vsprintf($info['format'], $values);
        } else {
            $value = implode(' ', $values);
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
                $detail['url'] = 'tel:'.strtr($value, '-', '');
                $detail['class'] = 'phone';
                break;
        
            case 'map':
                // Only send the next-to-last line of the address to the map module
                $lines = explode('$', $value);
                $count = count($lines);
                $linkAddress = ($count > 1) ? $lines[$count - 2] : $value;
                $detail['url'] = self::buildURLForModule('map', 'search', array(
                      'filter' => $linkAddress
                ));
                $detail['class'] = 'map';
                break;
        }
    
        $detail['title'] = str_replace('$', '<br />', $detail['title']); // $ is the LDAP multiline char
        return $detail;
    }
  
    private function formatPersonDetail(Person $person, $info, $key=0) {
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
                }
            }
          
            foreach ($valueGroups as $valueGroup) {
                $section[$key] = $this->formatDetail($valueGroup, $info, $person);
            }
        }
        
        return $section;
    }
  
    private function formatPersonDetails(Person $person) {
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
  
    public function federatedSearch($searchTerms, $maxCount, &$results) {
        $total = 0;
        $results = array();
      
        $PeopleController = $this->getFeed('people');
        
        $people = $PeopleController->search($searchTerms);
    
        if ($people !== false) {
            $limit = min($maxCount, count($people));
            for ($i = 0; $i < $limit; $i++) {
                $section = $this->formatPersonDetail($people[$i], $this->detailFields['name']);
            
                $results[] = array(
                    'url' => $this->buildBreadcrumbURL('detail', array(
                        'uid'    => $people[$i]->getId(),
                        'filter' => $searchTerms
                    ), false),
                    'title' => htmlentities($section[0]['title']),
                );
            }
        }
        
        return count($people);
    }
  
    protected function getFeed($index) {

        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = $this->defaultController;
            }
            $controller = PeopleController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setAttributes($this->detailAttributes);
            $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
            return $controller;
        } else {
            throw new Exception("Error getting people feed for index $index");
        }
    }

  
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
        $this->detailFields = $this->loadPageConfigFile('detail', 'detailFields');
        foreach($this->detailFields as $field => $info) {
            $this->detailAttributes = array_merge($this->detailAttributes, $info['attributes']);
        }
        $this->detailAttributes = array_unique($this->detailAttributes);
    }

    protected function initializeForPage() {

        $PeopleController = $this->getFeed('people');
        
        if (Kurogo::getSiteVar('MODULE_DEBUG')) {
            $this->addModuleDebugString($PeopleController->debugInfo());
        }
    
        switch ($this->page) {
        
            case 'detail':
                if ($uid = $this->getArg('uid')) {
                    $person = $PeopleController->lookupUser($uid);
          
                    if ($person) {
                        $this->assign('personDetails', $this->formatPersonDetails($person));
                        $section = $this->formatPersonDetail($person, $this->detailFields['name']);
                        // Bookmark
                        $cookieParams = array(
                            'title'   => htmlentities($section[0]['title']),
                            'uid' => urlencode($uid)
                        );
        
                        $cookieID = http_build_query($cookieParams);
                        $this->generateBookmarkOptions($cookieID);
                        break;
                    } else {
                        $this->assign('searchError', $PeopleController->getError());
                    }          
                } else {
                    $this->assign('searchError', 'No username specified');
                }
        
            case 'search':
                if ($filter = $this->getArg('filter')) {
                    $searchTerms = trim($filter);
          
                    $this->assign('searchTerms', $searchTerms);
          
                    $people = $PeopleController->search($searchTerms);
                    $this->assign('searchError', $PeopleController->getError());

                    if ($people !== false) {
                        $resultCount = count($people);
            
                        switch ($resultCount) 
                        {
                            case 0:
                                break;
                          
                            case 1:
                                $person = $people[0];
                                $this->redirectTo('detail', array(
                                    'uid'=>$person->getId()
                                    )
                                );
                                break;
                            
                            default:
                                $results = array();
                                
                                foreach ($people as $person) {
                                    $section = $this->formatPersonDetail($person, $this->detailFields['name']);
                                  
                                    $results[] = array(
                                        'url' => $this->buildBreadcrumbURL('detail', array(
                                            'uid'    => $person->getId(),
                                            'filter' => $this->getArg('filter')
                                        )),
                                        'title' => htmlentities($section[0]['title']),
                                    );
                                }
                                //error_log(print_r($results, true));
                                $this->assign('resultCount', $resultCount);
                                $this->assign('results', $results);
                                break;
                        }
                      
                    } else {
                        $this->assign('searchError', $PeopleController->getError());
                    }
                } else {
                  $this->redirectTo('index');
                }
                break;

            case 'bookmarks':
            	
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
            
                break;
        
            case 'index':
                $this->loadPageConfigFile('index', 'contacts');
                $this->setAutoPhoneNumberDetection(false);
                $this->generateBookmarkLink();
                break;
        }  
    }
}
