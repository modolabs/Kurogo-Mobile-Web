<?php

class PeopleAPIModule extends APIModule
{
    protected $id = 'people';
    private $fieldConfig;
    
    private function formatPerson($person) {
        $result = array();

        foreach ($this->fieldConfig as $returnField => $fieldOptions) {
            $label = $fieldOptions['label'];
            $attributes = array();
            foreach ($fieldOptions['attributes'] as $attribute) {
                $values = $person->getField($attribute);
                if ($values) {
                    if (is_array($values)) {
                        $attributes[] = $values[0];
                    } else {
                        $attributes[] = $values;
                    }
                }
            }
            if ($attributes) {
                if (isset($fieldOptions['format'])) {
                    $value = vsprintf($fieldOptions['format'], $attributes);
                } else {
                    $value = $attributes[0];
                }
                $result[$returnField] = $value;
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
                $feedData['CONTROLLER_CLASS'] = 'LDAPDataController';
            }
            $controller = PeopleController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            //$controller->setAttributes($this->detailAttributes);
            $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
            return $controller;
        } else {
            throw new Exception("Error getting people feed for index $index");
        }
    }

    public function initializeForCommand() {  
        $this->feeds = $this->loadFeedData();
        $peopleController = $this->getFeed('people');
        $this->fieldConfig = $this->loadAPIConfigFile('people');
        
        switch ($this->command) {
            case 'search':
                if ($filter = $this->getArg('q')) {
    
                    $searchTerms = trim($filter);
                    $people = $peopleController->search($searchTerms);
                    
                    $errorCode = $peopleController->getErrorNo();
                    if ($errorCode) {
                        // TODO decide on error title
                        $errorTitle = 'Warning';
                        $errorMsg = $peopleController->getError();
                        $error = new KurogoError($errorCode, $errorTitle, $errorMsg);
                        $this->setResponseError($error);
                    }
                    
                    if ($people !== false) {
                        $results = array();
                        $resultCount = count($people);
                        foreach ($people as $person) {
                            $results[] = $this->formatPerson($person);
                        }
                        $response->total = $resultCount;
                        $response->returned = $resultCount;
                        $response->displayField = 'name';
                        $response->results = $results;
                    }
                    
                    $this->setResponse($response);
                    $this->setResponseVersion(1);
                        
                } else {
                    $this->invalidCommand();
                    $this->setResponseVersion(1);
                }
                break;
            case 'displayfields':
                //break;
            case 'contacts':
                //break;
            default:
                $this->invalidCommand();
                break;
        }
    }
}

