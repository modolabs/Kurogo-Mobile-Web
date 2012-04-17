<?php

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
    
    private function formatPerson($person) {
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
                    if (is_array($values)) {
                        $delimiter = isset($fieldOptions['delimiter']) ? $fieldOptions['delimiter'] : ' ';
                        $attributes[$label] = implode($delimiter, $values);
                    } else {
                        $attributes[$label] = $values;
                    }
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
                    $result[$section][] = $valueArray;
                } else {
                    $result[$fieldOptions['label']] = $value;
                }
            }
            
        }
        return $result;
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

    public function initializeForCommand() {  
        $this->feeds = $this->loadFeedData();
        $this->fieldConfig = $this->getAPIConfigData('detail');
        foreach($this->fieldConfig as $field => $info) {
            $this->detailAttributes = array_merge($this->detailAttributes, $info['attributes']);
        }
        $this->detailAttributes = array_values(array_unique($this->detailAttributes));
        $peopleController = $this->getFeed('people');
        
        switch ($this->command) {
            case 'search':
                if ($filter = $this->getArg('q')) {

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
                            $results[] = $this->formatPerson($person);
                        }
                        $response = array(
                            'total'        => $resultCount,
                            'returned'     => $resultCount,
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
            default:
                $this->invalidCommand();
                break;
        }
    }
}

