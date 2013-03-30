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
  * @package People
  */

/**
  * @package People
  */
class DatabasePeopleRetriever extends DatabaseDataRetriever implements PeopleRetriever {
    const MIN_NAME_SEARCH = 3;
    
    protected $DEFAULT_PARSER_CLASS = 'DatabasePeopleParser';
    protected $table;
    protected $fieldMap=array();
    protected $personClass = 'DatabasePerson';
    protected $sortFields=array('lastname','firstname');
    protected $attributes = array();
    protected $searchFields = array();

    public function debugInfo() {
        return sprintf("Using Database");
    }
    
    public function getCacheKey() {
        return false;
    }
    
    protected function getSearchFields() {
        if ($this->searchFields) {
            $defaultFields = array(
                $this->getField('firstname'),
                $this->getField('lastname'),
                $this->getField('email')
            );
            
            if ($searchFields = array_diff($this->searchFields, $defaultFields)) {
                return array_unique($searchFields);
            }
        }
        
        return null;
    }
    
    protected function buildSearchQuery($searchString) {
        $sql = "";
        $parameters = array();

        if (empty($searchString)) {
            $this->errorMsg = "Query was blank";
            return;  
        } elseif (Validator::isValidEmail($searchString)) {
            $sql = sprintf("SELECT %s FROM %s WHERE %s LIKE ?", '*', $this->table, $this->getField('email'));
            $parameters = array('%'.$searchString.'%');
        } elseif ($this->getField('phone') && Validator::isValidPhone($searchString, $phone_bits)) {
            array_shift($phone_bits);
            $searchString = implode("", $phone_bits); // remove any separators. This might be an issue for people with formatted numbers in their directory
            $sql = sprintf("SELECT %s FROM %s WHERE %s LIKE ?", '*', $this->table, $this->getField('phone'));
            $parameters = array($searchString.'%');
        } elseif ($this->getField('phone') && preg_match('/^[0-9]+/', $searchString)) { //partial phone number
            $sql = sprintf("SELECT %s FROM %s WHERE %s LIKE ?", '*', $this->table, $this->getField('phone'));
            $parameters = array($searchString.'%');
        } elseif (strlen(trim($searchString)) < self::MIN_NAME_SEARCH) {
            $sql = sprintf("SELECT %s FROM %s WHERE %s = ? OR %s = ?", '*', $this->table, $this->getField('firstname'), $this->getField('lastname'));
            $parameters = array($searchString, $searchString);
        } elseif (preg_match('/[A-Za-z]+/', $searchString)) { // assume search by name

            $names = preg_split("/\s+/", $searchString);
            $nameCount = count($names);
            $where = array();

            switch ($nameCount)
            {
                case 1:
                    //try first name, last name and email
                    $where = sprintf("(%s LIKE ? OR %s LIKE ? OR %s LIKE ?)", $this->getField('firstname'), $this->getField('lastname'), $this->getField('email'));
                    $parameters = array($searchString.'%', $searchString.'%', '%'.$searchString.'%');
                    break;
                case 2:
                    $where = sprintf("((%s LIKE ? AND %s LIKE ?) OR (%s LIKE ? AND %s LIKE ?))",
                        $this->getField('firstname'), $this->getField('lastname'),
                        $this->getField('lastname'), $this->getField('firstname')
                    );
                        
                    $parameters = array($names[0].'%', $names[1].'%', $names[0].'%', $names[1].'%');
                    break;                
                    
                default:
                    // Either the first word is the first name, or it's a title and the 
                    // second word is the first name.
                    $possibleFirstNames = array($names[0], $names[1]);
                    
                    // Either the last word is the last name, or the last two words taken
                    // together are the last name.
                    $possibleLastNames = array($names[$nameCount - 1],
                                               $names[$nameCount - 2] . " " . $names[$nameCount - 1]);

                    
                    $parameters = array();
                    foreach ($possibleFirstNames as $i => $firstName) {
                        foreach ($possibleLastNames as $j => $lastName) {
                            $where[] = sprintf("(%s LIKE ? AND %s LIKE ?)", $this->getField('firstname'), $this->getField('lastname'));
                            $parameters[] = $firstName;
                            $parameters[] = $lastName;
                        }
                    }

                    $where = implode(" OR ", $where);
            }
            
            //build search for additional fields
            if ($searchField = $this->getSearchFields()) {
                $fieldWhere = array();
                foreach ($searchField as $field) {
                    $fieldWhere[] = sprintf("%s LIKE ?", $field);
                    $parameters[] = "%" . $searchString . "%";
                }
                
                $where .= ' OR ' . implode(" OR ", $fieldWhere);
            }
            
            $sql = sprintf("SELECT %s FROM %s WHERE %s ORDER BY %s", '*', $this->table, $where, implode(",", array_map(array($this,'getField'),$this->sortFields)));

        } else {
            $this->errorMsg = "Invalid query";
            return false;
        }    

        return array($sql, $parameters);
    }
    
    public function search($searchString, &$response=null) {
        $this->setQuery($this->buildSearchQuery($searchString));
        $this->setOption('action', 'search');
        $this->setContext('value', $searchString);

        return $this->getData($response);
    }
    
    protected function getField($_field) {
        if (array_key_exists($_field, $this->fieldMap)) {
            return $this->fieldMap[$_field];
        }

        return $_field;
    }
    
    protected function buildUserQuery($id) {
        $sql = sprintf("SELECT %s FROM %s WHERE %s=?", '*', $this->table, $this->getField('userid'));
        $parameters = array($id);
        
        return array($sql, $parameters);
    }

    /* returns a person object on success
    * FALSE on failure
    */
    public function getUser($id) {
        $this->setQuery($this->buildUserQuery($id));
        $this->setOption('action','user');
        $this->setContext('value', $id);

        return $this->getData($response);
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }

    protected function init($args) {
        parent::init($args);
        
        if (isset($args['SORTFIELDS']) && is_array($args['SORTFIELDS'])) {
            $this->sortFields = $args['SORTFIELDS'];
        }
                        
        $this->table = isset($args['DB_USER_TABLE']) ? $args['DB_USER_TABLE'] : 'users';

        $this->fieldMap = array(
            'userid'=>isset($args['DB_USERID_FIELD']) ? $args['DB_USERID_FIELD'] : 'userID',
            'email'=>isset($args['DB_EMAIL_FIELD']) ? $args['DB_EMAIL_FIELD'] : 'email',
            'firstname'=>isset($args['DB_FIRSTNAME_FIELD']) ? $args['DB_FIRSTNAME_FIELD'] : 'firstname',
            'lastname'=>isset($args['DB_LASTNAME_FIELD']) ? $args['DB_LASTNAME_FIELD'] : 'lastname',
            'phone'=>isset($args['DB_PHONE_FIELD']) ? $args['DB_PHONE_FIELD'] : ''
        );
        
        if (isset($args['SEARCH_FIELDS'])) {
            $this->searchFields = $args['SEARCH_FIELDS'];
        }
        
        $this->setContext('fieldMap',$this->fieldMap);
    }
}

class DatabasePeopleParser extends PeopleDataParser
{
    protected $personClass = 'DatabasePerson';
    
    public function parseData($data) {
        throw new KurogoException("Parse data not supported");
    }
        
    public function parseResponse(DataResponse $response) {
        $this->setResponse($response);
        
        $result = $response->getResponse();    
        if (!$result instanceOf PDOStatement) {
            return false;
        }

        $fieldMap = $response->getContext('fieldMap');
        
        switch ($this->getOption('action')) {
            case 'search':
                $results = array();
                while ($row = $result->fetch()) {
                    $person = new $this->personClass();
                    $person->setFieldMap($fieldMap);
                    $person->setAttributes($row);
                    $results[] = $person;
                }
                $this->setTotalItems(count($results));
                return $results;
                break;
                
            case 'user':
                $person = false;
                if ($row = $result->fetch()) {
                    $person = new $this->personClass();
                    $person->setFieldMap($fieldMap);
                    $person->setAttributes($row);
                }
                
                $result->closeCursor();
                $this->setTotalItems($person ? 1 : 0);   
                return $person;
        }

    }
}

class DatabasePerson extends Person 
{
    protected $fieldMap = array();
    
    public function setFieldMap(array $fieldMap) {
        $this->fieldMap = $fieldMap;
    }
    
    public function getName() {
    	return sprintf("%s %s", 
    			$this->getField($this->fieldMap['firstname']), 
    			$this->getField($this->fieldMap['lastname']));
    }

    public function getId() {
        return $this->getField(strtolower($this->fieldMap['userid']));
    }

    public function setAttributes($data) {
        foreach ($data as $field=>$value) {
            if (strlen($value)>0) {
                $this->setField(strtolower($field), $value);
            }
        }
    }    

    public function getAttributes(){
        return $this->attributes;
    }
}
