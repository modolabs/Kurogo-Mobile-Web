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

Kurogo::includePackage('LDAP');

if (!function_exists('ldap_connect')) {
    throw new KurogoException('LDAP PHP extension is not installed');
}

// common ldap error codes
define("LDAP_TIMELIMIT_EXCEEDED", 0x03);
define("LDAP_SIZELIMIT_EXCEEDED", 0x04);
define("LDAP_PARTIAL_RESULTS", 0x09);
define("LDAP_ADMINLIMIT_EXCEEDED", 0x0B);
define("LDAP_INSUFFICIENT_ACCESS", 0x32);

/**
  * @package People
  */
class LDAPPeopleRetriever extends DataRetriever implements PeopleRetriever
{
    const MIN_NAME_SEARCH = 3;
    
    protected $DEFAULT_PARSER_CLASS = 'LDAPPeopleParser';
    protected $MIN_PHONE_SEARCH = PeopleRetriever::MIN_PHONE_SEARCH;
    protected $personClass = 'LDAPPerson';
    protected $host;
    protected $port=389;
    protected $ldapResource;
    protected $searchBase;
    protected $adminDN;
    protected $adminPassword;
    protected $errorNo;
    protected $errorMsg;
    protected $filter;
    protected $searchTimelimit=30;
    protected $readTimelimit=30;
    protected $baseAttributes = array();
    protected $searchFields = array();
    protected $additionalFilters = array();
    protected $useSurroundWildcard;
    
    protected function retrieveResponse() {
        $response = $this->initResponse();
        $response->setCode($this->errorNo);
        $response->setResponseError($this->errorMsg);
        if (!$this->filter) {
            return $response;
        }

        $ds = $this->connectToServer();
        if (!$ds) {
            $response->setResponseError("Could not connect to LDAP server");
            return $response;
        }

        if ($this->adminDN) {
            if (!ldap_bind($ds, $this->adminDN, $this->adminPassword)) {
                Kurogo::log(LOG_WARNING, "Error binding to LDAP Server $this->host for $this->adminDN: " . ldap_error($ds), 'data');
                $response->setResponseError("Could not connect to LDAP server");
                return $response;
            }
        }

        // suppress warnings on non-dev servers
        // about searches that go over the result limit
        if (!$this->debugMode) {
            $error_reporting = ini_get('error_reporting');
            error_reporting($error_reporting & ~E_WARNING);
        }

        if ($this->filter instanceOf LDAPFilter) {
            $result = @ldap_search($ds, $this->searchBase,
                strval($this->filter), $this->getAttributes(), 0, 0, 
                $this->searchTimelimit);
        } else {
            $result = @ldap_read($ds, $this->filter, "(objectclass=*)", $this->getAttributes(), 
                0, 0, $this->readTimelimit);
        }
        
        $error_code = ldap_errno($ds);
        $response->setResponse($result);
        $response->setCode($error_code);
        $response->setContext('ldap', $ds);

        if ($error_code) {
            $response->setResponseError($this->generateErrorMessage($error_code));
        }
        
        if (!$this->debugMode) {
            error_reporting($error_reporting);
        }    
        
        return $response;
    }
  
    public function debugInfo() {
        return   sprintf("Using LDAP Server: %s", $this->host);
    }

    public function setHost($host) {
        $this->host = $host;
    }

    public function host() {
        return $this->host;
    }

    public function connectToServer() {
        if (!$this->ldapResource) {
            if ($this->ldapResource = ldap_connect($this->host, $this->port)) {
                ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($this->ldapResource, LDAP_OPT_REFERRALS, 0);
            } else {
                Kurogo::log(LOG_WARNING, "Error connecting to LDAP Server $this->host using port $this->port", 'data');
            }
        }

        return $this->ldapResource;
    }

    public function search($searchString, &$response=null) {
        $this->filter = $this->buildSearchFilter($searchString);
        $this->setOption('action', 'search');

        return $this->getData($response);
    }
    
    public function setAttributes($attributes) {
        $this->attributes = array_merge($this->baseAttributes, $attributes);
    }

    public function getAttributes() {
        return $this->attributes;
    }

    protected function getField($_field) {
        if (array_key_exists($_field, $this->fieldMap)) {
            return $this->fieldMap[$_field];
        }

        return $_field;
    }

    protected function nameFilter($firstName, $lastName) {
        $givenNameFilter = new LDAPFilter($this->getField('firstname'), $firstName, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
        $snFilter = new LDAPFilter($this->getField('lastname'), $lastName, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
        $filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_AND, $givenNameFilter, $snFilter);
        return $filter;
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

    protected function buildSearchFilter($searchString) {

        $filter = $this->errorNo = $this->errorMsg = null;

        $objectClassQuery = new LDAPFilter('objectClass', 'person');
        $searchString = trim($searchString);

        if (empty($searchString)) {
            $this->errorMsg = "Query was blank";
        } elseif (Validator::isValidEmail($searchString)) {
            $filter = new LDAPFilter($this->getField('email'), $searchString);
        } elseif (Validator::isValidPhone($searchString, $phone_bits)) {
            array_shift($phone_bits);
            $searchString = implode("", $phone_bits); // remove any separators. This might be an issue for people with formatted numbers in their directory
            $filter = new LDAPFilter($this->getField('phone'), $searchString);
        } elseif (preg_match('/^[0-9]{'. $this->MIN_PHONE_SEARCH . ',}/', $searchString)) { //partial phone number
            $filter = new LDAPFilter($this->getField('phone'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_SURROUND);
        } elseif (strlen(trim($searchString)) < self::MIN_NAME_SEARCH) {
            $firstFilter = new LDAPFilter($this->getField('firstname'), $searchString);
            $lastFilter = new LDAPFilter($this->getField('lastname'), $searchString);
            $filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_OR, $firstFilter, $lastFilter);
        } elseif (preg_match('/[A-Za-z]+/', $searchString)) { // assume search by name
            $names = preg_split("/\s+/", $searchString);
            $nameCount = count($names);
            switch ($nameCount)
            {
                case 1:
                    //try first name, last name and email
                    // Use surround wildcard if specified in config
                    $filterWildCardOption = $this->useSurroundWildcard ? LDAPFilter::FILTER_OPTION_WILDCARD_SURROUND : LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING;
                    $snFilter = new LDAPFilter($this->getField('lastname'), $searchString, $filterWildCardOption); 
                    $givenNameFilter = new LDAPFilter($this->getField('firstname'), $searchString, $filterWildCardOption); 
                    $mailFilter = new LDAPFilter($this->getField('email'), $searchString, $filterWildCardOption); 

                    $filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_OR, $givenNameFilter, $snFilter, $mailFilter);
                    break;
                case 2:
                    $filter1 = $this->nameFilter($names[0], $names[1]);
                    $filter2 = $this->nameFilter($names[1], $names[0]);
                    $filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_OR, $filter1, $filter2);
                    break;                
                default:

                    $filters = array();
                    // Either the first word is the first name, or it's a title and the 
                    // second word is the first name.
                    $possibleFirstNames = array($names[0], $names[1]);
                    
                    // Either the last word is the last name, or the last two words taken
                    // together are the last name.
                    $possibleLastNames = array($names[$nameCount - 1],
                                               $names[$nameCount - 2] . " " . $names[$nameCount - 1]);
                    
                    foreach ($possibleFirstNames as $i => $firstName) {
                        foreach ($possibleLastNames as $j => $lastName) {
                            $filters[] = $this->nameFilter($firstName, $lastName);
                        }
                    }

                    // Kitchen sink -- just string them all together with wildcards
                    // and hope that it's a match on the common name.
                    $filters[] = new LDAPFilter('cn', implode("*", array_map(array('LDAPFilter', 'ldapEscape'), $names)), LDAPFilter::FILTER_OPTION_NO_ESCAPE);
                    
                    $filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_OR, $filters);
            }
            
            //build search for additional fields
            if (($searchFields = $this->getSearchFields()) && ($filter instanceOf LDAPCompoundFilter)) {
                foreach ($searchFields as $field) {
                    $fieldFilter = new LDAPFilter($field, $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_SURROUND);
                    $filter->addFilter($fieldFilter);
                }
            }
            
        } else {
            $this->errorMsg = "Invalid query";
        }
        
        if ($this->additionalFilters) {
        	$filters = array_merge(array($filter), $this->additionalFilters);
			$filter = new LDAPCompoundFilter(LDAPCompoundFilter::JOIN_TYPE_AND, $filters);
        }

        return $filter;
    }
    
    public function getUser($id) {
        $this->setOption('action', 'user');
        if (strstr($id, '=')) { 
            // assume we're looking up person by "dn" (distinct ldap name)
            $this->filter = $id;
        } else {
            $this->filter = $this->buildUserFilter($id);
        }

        return $this->getData();
    }
    
    protected function buildUserFilter($id) {
        return new LDAPFilter($this->getField('uid'), $id);
    }

    protected function generateErrorMessage($error_code) {
        $error_codes = array(
            // LDAP error codes.
            LDAP_SIZELIMIT_EXCEEDED =>'ERROR_TOO_MANY_RESULTS',
            LDAP_PARTIAL_RESULTS => 'ERROR_TOO_MANY_RESULTS',
            LDAP_TIMELIMIT_EXCEEDED => 'ERROR_SERVER',
            LDAP_INSUFFICIENT_ACCESS => 'ERROR_SERVER',
            LDAP_ADMINLIMIT_EXCEEDED => 'ERROR_TOO_MANY_RESULTS'
        );
        
        $key = isset($error_codes[$error_code]) ? $error_codes[$error_code] : 'ERROR_GENERIC_SERVER_ERROR';
        return Kurogo::getLocalizedString($key, $error_code, ldap_err2str($error_code));
    }

    protected function init($args) {
        $args['PERSON_CLASS'] = isset($args['PERSON_CLASS']) ? $args['PERSON_CLASS'] : $this->personClass;
    
        parent::init($args);
        
        if (isset($args['HOST'])) {
            $this->setHost($args['HOST']);
        }
        
        $this->port = isset($args['PORT']) ? $args['PORT'] : 389;
        $this->searchBase = isset($args['SEARCH_BASE']) ? $args['SEARCH_BASE'] : '';
        $this->adminDN = isset($args['ADMIN_DN']) ? $args['ADMIN_DN'] : null;
        $this->adminPassword = isset($args['ADMIN_PASSWORD']) ? $args['ADMIN_PASSWORD'] : null;
        $this->searchTimelimit = isset($args['SEARCH_TIMELIMIT']) ? $args['SEARCH_TIMELIMIT'] : 30;
        $this->readTimelimit = isset($args['READ_TIMELIMIT']) ? $args['READ_TIMELIMIT'] : 30;
        
        if (isset($args['ATTRIBUTES'])) {
            $this->attributes = $args['ATTRIBUTES'];
            $this->baseAttributes = $args['ATTRIBUTES'];
        }

        if (isset($args['SEARCH_FIELDS'])) {
            $this->searchFields = $args['SEARCH_FIELDS'];
        }
        
        if (isset($args['LDAP_FILTER'])) {
        	if (!is_array($args['LDAP_FILTER'])) {
        		throw new KurogoConfigurationException("LDAP_FILTER expected to be an array");
        	}
        	foreach ($args['LDAP_FILTER'] as $filterString) {
        		$this->additionalFilters[] = new LDAPFilter($filterString, null, LDAPFilter::FILTER_OPTION_CUSTOM);
        	}
        }

        $this->useSurroundWildcard = Kurogo::arrayVal($args,'SURROUND_WILDCARD', false);
        
        $this->fieldMap = array(
            'userid'=>isset($args['LDAP_USERID_FIELD']) ? $args['LDAP_USERID_FIELD'] : 'uid',
            'email'=>isset($args['LDAP_EMAIL_FIELD']) ? $args['LDAP_EMAIL_FIELD'] : 'mail',
            'fullname'=>isset($args['LDAP_FULLNAME_FIELD']) ? $args['LDAP_FULLNAME_FIELD'] : '',
            'firstname'=>isset($args['LDAP_FIRSTNAME_FIELD']) ? $args['LDAP_FIRSTNAME_FIELD'] : 'givenname',
            'lastname'=>isset($args['LDAP_LASTNAME_FIELD']) ? $args['LDAP_LASTNAME_FIELD'] : 'sn',
            'photodata'=>isset($args['LDAP_PHOTODATA_FIELD']) ? $args['LDAP_PHOTODATA_FIELD'] : 'jpegphoto',
            'phone'=>isset($args['LDAP_PHONE_FIELD']) ? $args['LDAP_PHONE_FIELD'] : 'telephonenumber'
        );
        $this->setContext('fieldMap', $this->fieldMap);
    }
}

class LDAPPeopleParser extends PeopleDataParser
{
    protected $personClass = 'LDAPPerson';
    protected $sortFields=array('sn','givenname');
    
    public function parseData($data) {
        throw new KurogoException("Parse data not supported");
    }
    
    protected function parseSearch($data, $ds, $fieldMap) {
        $entry = ldap_first_entry($ds, $data);
        
        if (!$entry) {
            return array();
        }
        
        $results = array();
        $person = new $this->personClass($ds, $entry, $fieldMap);
        $results[$person->getID()] = $person;

        while ($entry = ldap_next_entry($ds, $entry)) {
            $person = new $this->personClass($ds, $entry, $fieldMap);

            if(isset($results[$person->getID()])){
                $existingPerson = $results[$person->getID()];
                $existingPerson->addFields($ds, $entry, $fieldMap);
                $results[$person->getID()] = $existingPerson;
            }else{
                $results[$person->getID()] = $person;
            }
        }

        return $this->sortResults($results);
    }

    protected function sortResults($results){
        //sort results by sort fields        
        if (count($results)>1) {
            $sprintf = implode(" ", array_fill(0, count($this->sortFields), '%s'));
            $sortTemp = array();
            foreach ($results as $key=>$person) {
                $sortFields = $this->sortFields;
                foreach ($sortFields as &$field) {
                    $field = $person->getFieldSingle($field);
                }
                $sortValue = vsprintf($sprintf, $sortFields);
                $sortTemp[$key] = $sortValue;
            }
            
            asort($sortTemp);
            
            foreach ($sortTemp as $key=>$sort) {
                $return[] = $results[$key];
            }
            $results = $return;
        } else {
            $results = array_values($results);
        }
        return $results;
    }
        
    public function parseResponse(DataResponse $response) {
        $this->setResponse($response);
        
        $data = $response->getResponse();    

        if (!is_resource($data) || get_resource_type($data) != 'ldap result') {
            return false;
        }

        $ds = $response->getContext('ldap');
        $fieldMap = $response->getContext('fieldMap');
        
        $parsedData = $this->parseSearch($data, $ds, $fieldMap);
        if ($this->getOption('action') == 'user') {

            $parsedData = current($parsedData);
            $this->setTotalItems($parsedData ? 1 : 0);
        } else {
            $this->setTotalItems(count($parsedData));
        }
        return $parsedData;
    }
    
    public function init($args) {
        parent::init($args);
        
        if (isset($args['SORTFIELDS']) && is_array($args['SORTFIELDS'])) {
            $this->sortFields = $args['SORTFIELDS'];
        }
    }    
}
    
class LDAPPerson extends Person {
    
    protected $dn;
    protected $fieldMap=array();
    protected $photoMIMEType = 'image/jpeg';
    
    public function getDn() {
        return $this->dn;
    }

    public function getName() {
        if ($this->fieldMap['fullname']) {
            return $this->getFieldSingle($this->fieldMap['fullname']);
        } else {
            return trim(sprintf("%s %s", 
                    $this->getFieldSingle($this->fieldMap['firstname']), 
                    $this->getFieldSingle($this->fieldMap['lastname'])));
        }
    }
    
    public function getId() {
        $uid = $this->getFieldSingle('uid');
        return $uid ? $uid : $this->getDn();
    }
    
    public function getFieldSingle($field) {
        $values = $this->getField($field);
        if ($values) {
            return $values[0];
        }
        return NULL;
    }
    
    public function getPhotoMIMEType() {
        return $this->photoMIMEType;
    }
    
    public function getPhotoData() {
        return $this->getFieldSingle($this->fieldMap['photodata']);
    }

    public function __construct($ldap, $entry, array $fieldMap) {
        $this->addFields($ldap, $entry, $fieldMap);
    }

    public function addFields($ldap, $entry, array $fieldMap){
        $ldapEntry = ldap_get_attributes($ldap, $entry);
        $this->dn = ldap_get_dn($ldap, $entry);
        $this->fieldMap = $fieldMap;
    
        for ($i = 0; $i < $ldapEntry['count']; $i++) {
            $attribute = $ldapEntry[$i];
            $attrib = strtolower($attribute);
            $count = $ldapEntry[$attribute]['count'];

            if ($attrib == $this->fieldMap['photodata']) {
                if ($data = @ldap_get_values_len($ldap, $entry, $attribute)) {
                    $this->setField($attrib, $data); // Get binary photo data
                }
            } else {
                $this->setField($attrib, array());
                for ($j = 0; $j < $count; $j++) {
                    if (!in_array($ldapEntry[$attribute][$j], $this->attributes[$attrib])) {
                        if(strlen($ldapEntry[$attribute][$j])){
                            $this->setFieldArray($attrib,str_replace('$', "\n", $ldapEntry[$attribute][$j]));
                        }
                    }
                }
            }
        }
    }
}
