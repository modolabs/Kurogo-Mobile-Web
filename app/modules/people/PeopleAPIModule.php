<?php

includePackage('People');

class PeopleAPIModule extends APIModule
{
    protected $id = 'people';
    protected $vmin = 1;
    protected $vmax = 1;
    private $fieldConfig;
    
    private function formatPerson($person) {
        $result = array();
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
                        $attributes[$label] = $values[0];
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
                        'label' => $fieldOptions['label'],
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
    
    // from PeopleWebModule
    protected function getFeed($index)
    {
        if (isset($this->feeds[$index])) {
            $feedData = $this->feeds[$index];
            if (!isset($feedData['CONTROLLER_CLASS'])) {
                $feedData['CONTROLLER_CLASS'] = 'LDAPPeopleController';
            }
            $controller = PeopleController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            //$controller->setAttributes($this->detailAttributes);
            $controller->setDebugMode(Kurogo::getSiteVar('DATA_DEBUG'));
            return $controller;
        } else {
            throw new Exception("Error getting people feed for index $index");
        }
    }

    public function initializeForCommand() {  
        $this->feeds = $this->loadFeedData();
        $peopleController = $this->getFeed('people');
        $this->fieldConfig = $this->getAPIConfigData('detail');
        
        switch ($this->command) {
            case 'search':
                if ($filter = $this->getArg('q')) {
                    
                    $people = $peopleController->search($filter);
                    
                    $errorCode = $peopleController->getErrorNo();
                    if ($errorCode) {
                        // TODO decide on error title
                        $errorTitle = 'Warning';
                        $errorMsg = $peopleController->getError();
                        $error = new KurogoError($errorCode, $errorTitle, $errorMsg);
                        $this->setResponseError($error);
                    }
                    
                    $response = null;
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
                    $this->setResponseVersion(1);
                        
                } else {
                    $this->invalidCommand();
                    $this->setResponseVersion(1);
                }
                break;
            case 'contacts':
                $results = $this->getAPIConfigData('contacts');
                $response = array(
                    'total'        => count($results),
                    'returned'     => count($results),
                    'displayField' => 'title',
                    'results'      => $results,
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

