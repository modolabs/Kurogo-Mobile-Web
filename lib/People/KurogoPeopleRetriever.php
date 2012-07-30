<?php
/**
  * @package People
  */

/**
  * @package People
  */
class KurogoPeopleRetriever extends URLDataRetriever implements PeopleRetriever {
    protected $DEFAULT_PARSER_CLASS = "KurogoPeopleParser";
    protected $fieldMap=array();
    protected $personClass = 'KurogoPerson';
    protected $attributes = array();
    protected $peopleAPI = '';
    protected $feed;

    public function getCacheKey() {
        return false;
    }
    
    public function debugInfo() {
        return sprintf("Using Kurogo People API");
    }
    
    public function search($searchString, &$response=null) {
        $this->setBaseURL($this->peopleAPI . '/search');
        
        $this->setOption('action', 'search');
        
        $this->addFilter('q', $searchString);
        $this->addFilter('output', 'fields');
        if($this->feed){
            $this->addFilter('feed', $this->feed);    
        }

        return $this->getData($response);
    }
    
    protected function getField($_field) {
        if (array_key_exists($_field, $this->fieldMap)) {
            return $this->fieldMap[$_field];
        }

        return $_field;
    }

    /* returns a person object on success
    * FALSE on failure
    */
    
    public function getUser($id) {
        
        $this->setBaseURL($this->peopleAPI . '/detail');
        $this->setOption('action','detail');
        
        $this->addFilter('uid', $id);
        $this->addFilter('output', 'fields');
        if($this->feed){
            $this->addFilter('feed', $this->feed);    
        }

        return $this->getData($response);
    }
    
    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }

    protected function init($args) {
        parent::init($args);

        $this->fieldMap = array(
            'userid'=>isset($args['KUROGO_USERID_FIELD']) ? $args['KUROGO_USERID_FIELD'] : 'uid',
            'email'=>isset($args['KUROGO_EMAIL_FIELD']) ? $args['KUROGO_EMAIL_FIELD'] : 'mail',
            'fullname'=>isset($args['KUROGO_FULLNAME_FIELD']) ? $args['KUROGO_FULLNAME_FIELD'] : '',
            'firstname'=>isset($args['KUROGO_FIRSTNAME_FIELD']) ? $args['KUROGO_FIRSTNAME_FIELD'] : 'givenname',
            'lastname'=>isset($args['KUROGO_LASTNAME_FIELD']) ? $args['KUROGO_LASTNAME_FIELD'] : 'sn',
            'photodata'=>isset($args['KUROGO_PHOTODATA_FIELD']) ? $args['KUROGO_PHOTODATA_FIELD'] : 'jpegphoto',
            'phone'=>isset($args['KUROGO_PHONE_FIELD']) ? $args['KUROGO_PHONE_FIELD'] : 'telephonenumber'
        );
        
        if (isset($args['SORTFIELDS']) && is_array($args['SORTFIELDS'])) {
            $this->sortFields = $args['SORTFIELDS'];
        }

        if (isset($args['BASE_URL']) && $args['BASE_URL']) {
            $this->peopleAPI = rtrim($args['BASE_URL'], '/');
        }

        $this->feed = Kurogo::arrayVal($args, 'FEED');
        
        $this->setContext('fieldMap', $this->fieldMap);
    }
}

class KurogoPeopleParser extends PeopleDataParser
{
    protected $personClass = 'KurogoPerson';
    
    public function parseData($data) {
        throw new KurogoException("Parse data not supported");
    }
        
    public function parseResponse(DataResponse $response) {
        $this->setResponse($response);
        $data = $response->getResponse();
        
        $fieldMap = $response->getContext('fieldMap');

        if ($result = json_decode($data, true)) {
            if (isset($result['error']) && $result['error']) {
                $response->setCode($result['error']['code']);
                $response->setResponseError($result['error']['message']);
            }
            switch ($this->getOption('action')) {
            	case 'search':
                    if (isset($result['response']['total']) && $result['response']['total'] > 0) {
            		    $results = array();
            		    foreach ($result['response']['results'] as $item) {
            		        $person = new $this->personClass();
            		        $person->setFieldMap($fieldMap);
            		        $person->setAttributes($item);
            		        $results[] = $person;
            		    }
            		    
            		    $this->setTotalItems(count($results));
            			return $results;
            		}
            		break;
            	case 'detail':
                    if(isset($result['response']) && $result['response']['person']){
                        $person = new $this->personClass();
                        $person->setFieldMap($fieldMap);
                        $person->setAttributes($result['response']['person']);

                        return $person;
                    }
            	default:
            	    break;
            }
        }
        
        return array();
    }
}

class KurogoPerson extends Person 
{
    protected $fieldMap = array();
    protected $displayFields = array();
    
    public function setFieldMap($fieldMap) {
        $this->fieldMap = $fieldMap;
    }
    
    public function setDispalyFields($displayFields) {
        $this->displayFields = $displayFields;
    }
    
    public function getName() {
    	return sprintf("%s %s", 
    			$this->getFieldSingle($this->fieldMap['firstname']), 
    			$this->getFieldSingle($this->fieldMap['lastname']));
    }

    public function getId() {
        return $this->getFieldSingle($this->fieldMap['userid']);
    }
    
    public function getFieldSingle($field) {
        if ($values = $this->getField($field)) {
            if (is_array($values)) {
                return $values[0];
            } else {
                return $values;
            }
        }
        
        return null;
    }
    
    public function setAttributes($data) {
        foreach ($data as $field=>$value) {
            $this->attributes[$field] = $value;
        }
    }    

}
