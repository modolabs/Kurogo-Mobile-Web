<?php
/**
  * @package People
  */

if (!function_exists('ldap_connect')) {
    die('LDAP Functions not available');
}

// common ldap error codes
define("LDAP_TIMELIMIT_EXCEEDED", 0x03);
define("LDAP_SIZELIMIT_EXCEEDED", 0x04);
define("LDAP_PARTIAL_RESULTS", 0x09);
define("LDAP_INSUFFICIENT_ACCESS", 0x32);

/**
  * @package People
  */
class LDAPPeopleRetriever extends DataRetriever implements PeopleRetriever
{
    protected $DEFAULT_PARSER_CLASS = 'LDAPPeopleParser';
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
            $result = ldap_search($ds, $this->searchBase,
                strval($this->filter), $this->getAttributes(), 0, 0, 
                $this->searchTimelimit);
        } else {
            $result = ldap_read($ds, $this->filter, "(objectclass=*)", $this->getAttributes(), 
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
        $this->attributes = $attributes;
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

    protected function buildSearchFilter($searchString) {

        $this->errorNo = $this->errorMsg = null;

        $objectClassQuery = new LDAPFilter('objectClass', 'person');

        if (empty($searchString)) {
            $this->errorMsg = "Query was blank";
        } elseif (Validator::isValidEmail($searchString)) {
            $filter = new LDAPFilter($this->getField('email'), $searchString);
        } elseif (Validator::isValidPhone($searchString, $phone_bits)) {
            array_shift($phone_bits);
            $searchString = implode("", $phone_bits); // remove any separators. This might be an issue for people with formatted numbers in their directory
            $filter = new LDAPFilter($this->getField('phone'), $searchString);
        } elseif (preg_match('/^[0-9]+/', $searchString)) { //partial phone number
            $filter = new LDAPFilter($this->getField('phone'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING);
        } elseif (preg_match('/[A-Za-z]+/', $searchString)) { // assume search by name

            $names = preg_split("/\s+/", $searchString);
            $nameCount = count($names);
            switch ($nameCount)
            {
                case 1:
                    //try first name, last name and email
                    $snFilter = new LDAPFilter($this->getField('lastname'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
                    $givenNameFilter = new LDAPFilter($this->getField('firstname'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 
                    $mailFilter = new LDAPFilter($this->getField('email'), $searchString, LDAPFilter::FILTER_OPTION_WILDCARD_TRAILING); 

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

        } else {
            $this->errorMsg = "Invalid query";
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
            LDAP_SIZELIMIT_EXCEEDED => "There are more results than can be displayed. Please refine your search.",
            LDAP_PARTIAL_RESULTS => "There are more results than can be displayed. Please refine your search.",
            LDAP_TIMELIMIT_EXCEEDED => "The directory service is not responding. Please try again later.",
            LDAP_INSUFFICIENT_ACCESS => "Insufficient permission to view this information.",
        );

        if(isset($error_codes[$error_code])) {
            return $error_codes[$error_code];
        } else { // return a generic error message
            return "Your request cannot be processed at this time. ($error_name)";
        }
    }

    protected function init($args) {
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

        $this->fieldMap = array(
            'userid'=>isset($args['LDAP_USERID_FIELD']) ? $args['LDAP_USERID_FIELD'] : 'uid',
            'email'=>isset($args['LDAP_EMAIL_FIELD']) ? $args['LDAP_EMAIL_FIELD'] : 'mail',
            'fullname'=>isset($args['LDAP_FULLNAME_FIELD']) ? $args['LDAP_FULLNAME_FIELD'] : '',
            'firstname'=>isset($args['LDAP_FIRSTNAME_FIELD']) ? $args['LDAP_FIRSTNAME_FIELD'] : 'givenname',
            'lastname'=>isset($args['LDAP_LASTNAME_FIELD']) ? $args['LDAP_LASTNAME_FIELD'] : 'sn',
            'phone'=>isset($args['LDAP_PHONE_FIELD']) ? $args['LDAP_PHONE_FIELD'] : 'telephonenumber'
        );
        $this->setContext('fieldMap', $this->fieldMap);
    }
}

/**
* @package People
*/
class LDAPFilter
{
    const FILTER_OPTION_WILDCARD_TRAILING=1;
    const FILTER_OPTION_WILDCARD_LEADING=2;
    const FILTER_OPTION_NO_ESCAPE=4;
    const FILTER_OPTION_WILDCARD_SURROUND=8;
    protected $field;
    protected $value;
    protected $options;

    public function __construct($field, $value, $options=0)
    {
        $this->field = $field;
        $this->value = $value;
        $this->options = intval($options);
    }
    
    public static function ldapEscape($str) 
    { 
        // see RFC2254 
        // http://msdn.microsoft.com/en-us/library/ms675768(VS.85).aspx 
        // http://www-03.ibm.com/systems/i/software/ldap/underdn.html        
        
        $metaChars = array('*', '(', ')', '\\', chr(0));
        $quotedMetaChars = array(); 
        foreach ($metaChars as $key => $value) {
            $quotedMetaChars[$key] = '\\'.str_pad(dechex(ord($value)), 2, '0'); 
        }
        $str = str_replace($metaChars, $quotedMetaChars, $str); 
        return ($str); 
    }
    
    public function __toString()
    {
        if ($this->options & self::FILTER_OPTION_NO_ESCAPE) {
            $value = $this->value;
        } else {
            $value = self::ldapEscape($this->value);
        }
    
        if ($this->options & self::FILTER_OPTION_WILDCARD_LEADING) {
            $value  = '*' . $value;
        }
    
        if ($this->options & self::FILTER_OPTION_WILDCARD_TRAILING) {
            $value  .= '*';
        }
    
        if ($this->options & self::FILTER_OPTION_WILDCARD_SURROUND) {
            $value  = '*' . $value . "*";
        }
    
        return sprintf("(%s=%s)", $this->field, $value);
    }
}
    
/**
* @package People
*/
class LDAPCompoundFilter extends LDAPFilter
{
    const JOIN_TYPE_AND='&';
    const JOIN_TYPE_OR='|';
    protected $joinType;
    protected $filters=array();
    
    public function __construct($joinType, $filter1, $filter2=null) {

        switch ($joinType)
        {
            case self::JOIN_TYPE_AND:
            case self::JOIN_TYPE_OR:
                $this->joinType = $joinType;
                break;
            default:
                throw new KurogoConfigurationException("Invalid join type $joinType");                
        }
    
        for ($i=1; $i < func_num_args(); $i++) {
            $filter = func_get_arg($i);
            if ($filter instanceOF LDAPFilter) { 
                $this->filters[] = $filter;
            } elseif (is_array($filter)) {
                foreach ($filter as $_filter) {
                    if (!($_filter instanceOf LDAPFilter)) {
                        throw new KurogoConfigurationException("Invalid filter for in array");
                    }
                }
                $this->filters = $filter;
            } else {
                throw new KurogoConfigurationException("Invalid filter for argument $i");
            }
        }
    
        if (count($this->filters)<2) {
            throw new KurogoConfigurationException(sprintf("Only %d filters found (2 minimum)", count($filters)));
        }
    
    }
    
    function __toString() {

        $stringValue = sprintf("(%s%s)", $this->joinType, implode("", $this->filters));
        return $stringValue;
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
            return FALSE;
        }
        
        $results = array();
        $person = new $this->personClass($ds, $entry);
        $person->setFieldMap($fieldMap);
        $results[] = $person;

        while ($entry = ldap_next_entry($ds, $entry)) {
			$person = new $this->personClass($ds, $entry);
			$person->setFieldMap($fieldMap);
			$results[] = $person;
        }

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
        }
                    
        return $results;
    }
        
    public function parseResponse(DataResponse $response) {

        $data = $response->getResponse();    

        if (!is_resource($data) || get_resource_type($data) != 'ldap result') {
            return false;
        }

        $ds = $response->getContext('ldap');
        $fieldMap = $response->getContext('fieldMap');
        
        $parsedData = $this->parseSearch($data, $ds, $fieldMap);
        if ($this->getOption('action')=='user') {
            $parsedData = isset($parsedData[0]) ? $parsedData[0] : false;
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
    
    public function getDn() {
        return $this->dn;
    }

    public function setFieldMap(array $fieldMap) {
        $this->fieldMap = $fieldMap;
    }
    
    public function getName() {
        if ($this->fieldMap['fullname']) {
            return $this->getFieldSingle($this->fieldMap['fullname']);
        } else {
            return sprintf("%s %s", 
                    $this->getFieldSingle($this->fieldMap['firstname']), 
                    $this->getFieldSingle($this->fieldMap['lastname']));
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
    
    public function __construct($ldap, $entry) {
        $ldapEntry = ldap_get_attributes($ldap, $entry);
        $this->dn = ldap_get_dn($ldap, $entry);
        $this->attributes = array();
    
        for ($i=0; $i<$ldapEntry['count']; $i++) {
            $attribute = $ldapEntry[$i];
            $attrib = strtolower($attribute);
            $count = $ldapEntry[$attribute]['count'];
            $this->attributes[$attrib] = array();
            for ($j=0; $j<$count; $j++) {
                if (!in_array($ldapEntry[$attribute][$j], $this->attributes[$attrib])) {
                    $this->attributes[$attrib][] = str_replace('$', "\n", $ldapEntry[$attribute][$j]);
                }
            }
        }
    }
}
