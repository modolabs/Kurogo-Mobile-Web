<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

Kurogo::includePackage('People');

class PeopleAPIModule extends APIModule
{
    protected $id = 'people';
    protected $vmin = 1;
    protected $vmax = 2;
    protected static $defaultModel = 'PeopleDataModel';
    protected static $defaultController = 'LDAPPeopleController'; //legacy
    private $fieldConfig;
    private $detailAttributes = array();
    protected $contactGroups = array();
    protected $legacyController;
    
    protected function getContactGroup($group) {
        if (!$this->contactGroups) {
            $this->contactGroups = $this->getAPIConfigData('contacts-groups');
        }
        
        if (isset($this->contactGroups[$group])) {
            return $this->contactGroups[$group];

        } else {
            throw new KurogoConfigurationException("Unable to find contact group information for $group");
        }
    }

    protected function getContactsForGroup($group) {
        if (!$this->contactGroups) {
            $this->contactGroups = $this->getAPIConfigData('contacts-groups');
        }
        
        if (isset($this->contactGroups[$group])) {
            return $this->getAPIConfigData('contacts-' . $group);

        } else {
            throw new KurogoConfigurationException("Unable to find contact group information for $group");
        }
    }
    
    private function formatPersonByNative($person) {
        $result = array();
        $result['uid'] = $person->getId();

        foreach ($this->fieldConfig as $fieldID => $fieldOptions) {
            $attributes = array();
            for ($i = 0; $i < count($fieldOptions['attributes']); $i++) {
                if (isset($fieldOptions['labels'])) {
                    $label = $fieldOptions['labels'][$i];
                } else {
                    $label = $i;
                }
            
                $attribute = $fieldOptions['attributes'][$i];
                $values = $person->getField($attribute);
                if ($values) {
                    if (self::argVal($fieldOptions, 'type') == 'imgdata') {
                        $attributes[$label] = FULL_URL_PREFIX.$this->configModule.'/photo?'.http_build_query(array('uid'=>$person->getID()));
                    } else if (is_array($values)) {
                        $delimiter = isset($fieldOptions['delimiter']) ? $fieldOptions['delimiter'] : ' ';
                        $attributes[$label] = implode($delimiter, $values);
                    } else {
                        $attributes[$label] = $values;
                    }
                } elseif (isset($fieldOptions['format'])) {
                	//always include attributes when using format
                	$attributes[$label] = null;
                }
            }
            
            // if we use format and there are no fields then skip
            if (isset($fieldOptions['format'])) {
            	if (!array_filter($attributes)) {
            		$attributes = array();
            	}
            }

            if ($attributes) {
                if (isset($fieldOptions['format'])) {
                    $value = vsprintf($fieldOptions['format'], $attributes);
                } elseif (isset($fieldOptions['parse'])) {
                    $formatFunction = create_function('$value', $fieldOptions['parse']);
                    $value = $formatFunction($attributes);
                } elseif (isset($fieldOptions['labels'])) {
                    $value = $attributes;
                } else {
                    $value = $attributes[0];
                }
                $url = NULL;
                if (self::argVal($fieldOptions, 'type') == 'map') {
                     $link = Kurogo::moduleLinkForValue('map', $value, $this, $person);
                     if (isset($link, $link['url'])) {
                         $url = $link['url'];
                     }
                }
                if (isset($fieldOptions['section'])) {
                    $section = $fieldOptions['section'];
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $valueArray = array(
                        'title' => $fieldOptions['label'],
                        'type' => $fieldOptions['type'],
                        'value' => $value,
                        );
                    if (isset($url)) {
                        $valueArray['url'] = $url;
                    }
                    $result[$section][] = $valueArray;
                } else {
                    $result[$fieldOptions['label']] = $value;
                }
            }
            
        }
        return $result;
    }
    
    private function formatPersonByFields($person) {
        $result = array();
        
        $result['uid'] = $person->getId();
        $result = array_merge($result, $person->getAttributes());
        
        return $result;
    }
    
    private function formatPerson($person, $output='') {
        if ($output == 'fields') {
            return $this->formatPersonByFields($person);
        } else {
            return $this->formatPersonByNative($person);
        }
        
    }
    
    protected function getFeed($index) {
        if (isset($this->controllers[$index])) {
            return $this->controllers[$index];
        }
        
        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];

            try {
                if (isset($feedData['CONTROLLER_CLASS'])) {
                    $modelClass = $feedData['CONTROLLER_CLASS'];
                } else {
                    $modelClass = isset($feedData['MODEL_CLASS']) ? $feedData['MODEL_CLASS'] : self::$defaultModel;
                }
                
                $controller = PeopleDataModel::factory($modelClass, $feedData);
            } catch (KurogoException $e) { 
                $controller = PeopleController::factory($feedData['CONTROLLER_CLASS'], $feedData);
                $this->legacyController = true;
            }
            
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

    protected function loadAPIDetailAttributes($feed){
        if($this->feeds){
            if(count($this->feeds) == 1){
                # Load detail fields from api-detail.ini
                $this->fieldConfig = $this->getAPIConfigData('detail');
            }else{
                # Load detail fields from page-detail-[feed].ini
                $detailConfig = "detail-$feed";
                $this->fieldConfig = $this->getAPIConfigData($detailConfig);
            }
            foreach($this->fieldConfig as $field => $info) {
                $this->detailAttributes = array_merge($this->detailAttributes, $info['attributes']);
            }
            $this->detailAttributes = array_values(array_unique($this->detailAttributes));
        }
    }
    
    public function initializeForCommand() {  
        $this->feeds = $this->loadFeedData();
        $feed = $this->getArg('feed', $this->getDefaultFeed());
        $this->loadAPIDetailAttributes($feed);
        $peopleController = $this->getFeed($feed);
        
        $output = $this->getArg('output', '');
        
        switch ($this->command) {
            case 'search':
                if ($filter = $this->getArg(array('filter', 'q'))) {

                    $people = $peopleController->search($filter);
                    if(!$people)
                    	$people = array();
                    	
                    $errorCode = $peopleController->getResponseCode();
                    if ($errorCode) {
                        // TODO decide on error title
                        $errorTitle = 'Warning';
                        $errorMsg = $peopleController->getResponseError();
                        $error = new KurogoError($errorCode, $errorTitle, $errorMsg);
                        $this->setResponseError($error);
                    }
                    
                    $response[] = null;
                    if ($people !== false) {
                        $results = array();
                        $resultCount = count($people);
                        foreach ($people as $person) {
                            $results[] = $this->formatPerson($person, $output);
                        }
                        $response = array(
                            'total'        => $resultCount,
                            'returned'     => $resultCount,
                            'feed'         => $feed,
                            'displayField' => 'name',
                            'results'      => $results,
                            );
                    }
                    
                    $this->setResponse($response);
                    $this->setResponseVersion(2);
                        
                } else {
                    $this->invalidCommand();
                    $this->setResponseVersion(1);
                }
                break;
                
            case 'detail':
            	
            	if($uid = $this->getArg(array('id', 'uid'))){
                    $person = $peopleController->getUser($uid);
                    if ($person) {
                        $personDetails =  $this->formatPerson($person, $output);
                        $response = array(
                            'person'    => $personDetails,
                        );

                        $this->setResponse($response);
                        $this->setResponseVersion(1);
                    }else{
                        $errorCode = $peopleController->getResponseCode();
                        if ($errorCode) {
                            // TODO decide on error title
                            $errorTitle = 'Warning';
                            $errorMsg = $peopleController->getResponseError();
                            $error = new KurogoError($errorCode, $errorTitle, $errorMsg);
                            $this->setResponseError($error);
                        }
                    }
                }else{
                    $this->invalidCommand();
                    $this->setResponseVersion(1);
                }
            	break;
                
            case 'contacts':
                $convertTags = array(
                    'class' => 'type',
                    'label' => 'title',
                    'value' => 'subtitle',
                    );

                $group = $this->getArg('group');
                if ($group) {
                    $results = $this->getContactsForGroup($group);
                } else {
                    $results = $this->getAPIConfigData('contacts');
                }

                foreach ($results as &$aResult) {
                    if (isset($aResult['group'])) {
                        $groupData = $this->getContactGroup($aResult['group']);
                        if (isset($groupData['description'])) {
                            $aResult['subtitle'] = $groupData['description'];
                        }
                    }

                    foreach ($convertTags as $from => $to) {
                        if (isset($aResult[$from])) {
                            $aResult[$to] = $aResult[$from];
                            unset($aResult[$from]);
                        }
                    }
                }

                $response = array(
                    'total'        => count($results),
                    'results'      => $results,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);

                break;
            case 'group':
            	$group = $this->getContactGroup($this->getArg('group'));
            	$response = array(
                    'total'        => count($group),
                    'results'      => $group,
                    );

                $this->setResponse($response);
                $this->setResponseVersion(1);
                
            	break;
            case 'displayfields':
                //break;
            case 'feeds':
                $feeds = array();
                foreach ($this->feeds as $key => $feedData) {
                    $feeds[] = array(
                    	'feed'=>$key,
                    	'title'=>Kurogo::arrayVal($feedData,'TITLE', $feed)
                    );
                }
                
                $response = $feeds;
                
                $this->setResponse($response);
                $this->setResponseVersion(1);
                break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}

