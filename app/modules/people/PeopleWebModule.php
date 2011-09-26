<?php
/**
  * @package Module
  * @subpackage People
  */
Kurogo::includePackage('People');

if (!function_exists('mb_convert_encoding')) {
    die('Multibyte String Functions not available (mbstring)');
}

/**
  * @package Module
  * @subpackage People
  */
class PeopleWebModule extends WebModule {
    protected $id = 'people';
    protected $detailFields = array();
    protected $detailAttributes = array();
    protected $defaultController = 'LDAPPeopleController';
    protected $encoding = 'UTF-8';
    protected $feeds=array();
    protected $contactGroups = array();
    protected $controllers = array();

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
        $feed = isset($options['feed']) ? $options['feed'] : 'people';
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
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = $this->defaultController;
            }
            $controller = PeopleController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setAttributes($this->detailAttributes);
            $this->controllers[$index] = $controller;
            return $controller;
        } else {
            throw new KurogoConfigurationException("Error getting people feed for index $index");
        }
    }

  
    protected function initialize() {
        $this->feeds = $this->loadFeedData();
        $this->detailFields = $this->loadPageConfigFile('detail', 'detailFields');
        foreach($this->detailFields as $field => $info) {
            $this->detailAttributes = array_merge($this->detailAttributes, $info['attributes']);
        }
        $this->detailAttributes = array_values(array_unique($this->detailAttributes));
    }
    
    public function linkforItem(KurogoObject $person, $options=null) {    
        return array(
            'title'=>$this->htmlEncodeString($person->getName()),
            'url'  =>$this->buildBreadcrumbURL('detail', array(
                                            'uid'    => $person->getId(),
                                            'filter' => $this->getArg('filter')
                    ))
        );
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
            $contacts = $this->loadPageConfigFile('index', 'contacts');
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

        $PeopleController = $this->getFeed('people');
        
        if (Kurogo::getSiteVar('MODULE_DEBUG')) {
            $this->addModuleDebugString($PeopleController->debugInfo());
        }
    
        switch ($this->page) {
        
            case 'detail':
                if ($uid = $this->getArg('uid')) {
                    $person = $PeopleController->lookupUser($uid);
          
                    if ($person) {
                    
                        $this->setLogData($uid, $person->getName());
                        $personDetails =  $this->formatPersonDetails($person);
                        // Bookmark
                        if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                            $cookieParams = array(
                                'title' => $person->getName(),
                                'uid' => urlencode($uid)
                            );
            
                            $cookieID = http_build_query($cookieParams);
                            $this->generateBookmarkOptions($cookieID);
                        }
                        $this->assign('personDetails', $personDetails);
                        break;
                    } else {
                        $this->assign('searchError', $PeopleController->getError());
                    }          
                } else {
                    $this->assign('searchError', 'No username specified');
                }
                break;
        
            case 'search':
                if ($filter = $this->getArg('filter')) {
                    $searchTerms = trim($filter);
          
                    $this->assign('searchTerms', $searchTerms);
          
                    $this->setLogData($searchTerms);
                    $people = $this->searchItems($searchTerms);
                    $this->assign('searchError', $PeopleController->getError());

                    if ($people !== false) {
                        $resultCount = count($people);
            
                        switch ($resultCount) 
                        {
                            case 1:
                                $person = $people[0];
                                $this->logView();
                                $this->redirectTo('detail', array(
                                    'uid'=>$person->getId()
                                    )
                                );
                                break;
                            
                            default:
                                $results = array();
                                
                                foreach ($people as $person) {
                                    $results[] = $this->linkforItem($person);
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
                break;
        
            case 'index':
            case 'pane':
                $contacts = $this->getContacts();
                $this->assign('contacts', $contacts);
                
                $this->setAutoPhoneNumberDetection(false);
                if ($this->getOptionalModuleVar('BOOKMARKS_ENABLED', 1)) {
                    $this->generateBookmarkLink();
                }
                $this->assign('placeholder', $this->getLocalizedString("SEARCH"));
                $this->assign('searchTip', $this->getOptionalModuleVar('SEARCH_TIP'));
                break;
        }  
    }
}
