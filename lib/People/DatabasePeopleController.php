<?php
/**
  * @package Directory
  */

/**
  * @package Directory
  */
class DatabasePeopleController extends PeopleController {
    protected $conn;
    protected $table;
    protected $fieldMap=array();
    protected $personClass = 'DatabasePerson';
    protected $sortFields=array('lastname','firstname');

    public function debugInfo() {
        return sprintf("Using Database");
    }
  
    public function search($searchString) {
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
                    $filters = array();
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

            $sql = sprintf("SELECT %s FROM %s WHERE %s ORDER BY %s", '*', $this->table, $where, implode(",", array_map(array($this,'getField'),$this->sortFields)));

        } else {
            $this->errorMsg = "Invalid query";
            return array();
        }
        
        $results = array();
        if ($result = $this->connection->query($sql, $parameters)) {
            while ($row = $result->fetch()) {
                $person = new $this->personClass();
                $person->setFieldMap($this->fieldMap);
                $person->setAttributes($row);
                $results[] = $person;
            }
        } 
        
        return $results;        
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
    public function lookupUser($id) {
        $sql = sprintf("SELECT %s FROM %s WHERE %s=?", '*', $this->table, $this->getField('userid'));
        $result = $this->connection->query($sql, array($id));
        $person = false;
        if ($row = $result->fetch()) {
            $person = new $this->personClass();
            $person->setFieldMap($this->fieldMap);
            $person->setAttributes($row);
        }

        return $person;
    }

    protected function init($args) {
        parent::init($args);
        
        $args = is_array($args) ? $args : array();
        if (!isset($args['DB_TYPE'])) {
            $args = array_merge(Kurogo::getSiteSection('database'), $args);
        }
        
        $this->connection = new db($args);                
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
            $this->attributes[strtolower($field)] = $value;
        }
    }    

}
