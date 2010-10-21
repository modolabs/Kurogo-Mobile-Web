<?php
define("SERVER", "ldap.mit.edu");

function mit_search($search) {
  $results = do_query(standard_query($search));
  if($email_query = email_query($search)) {
    $results = do_query($email_query, $results);
  }
  return order_results($results, $search);
}

function order_results($results, $search) {
    $low_priority = array();
    $high_priority = array();
    foreach($results as $result) {
        $item = make_person($result);
        if(has_priority($item, $search)) {
          $high_priority[] = $item;
        } else {
          $low_priority[] = $item;
        }  
    }
    
    //Alphabetize low_priority array
    usort($low_priority, "compare_people");
    return array_merge($high_priority, $low_priority);
}

function has_priority($item, $search) {
  $words = preg_split('/\s+/', trim($search));
  if(count($words) == 1) {
    $word = strtolower($words[0]);
    $emails = $item['email'];
    foreach($emails as $email) {
      $email = strtolower($email);
      if( ($email == $word) || 
          (substr($email, 0, strlen($word)+1) == "$word@") ) {
            return True;
      }
    } 
  }
  return False;
}

function email_query($search) {
  $words = preg_split('/\s+/', trim($search));
  if(count($words) == 1) {
    $word = $words[0];
    if(strpos($words, '@') === False) {
      //turns blpatt into blpatt@*
      $word .= '@*';
    }
    return new QueryElement('mail', $word);
  }
}
    
function standard_query($search) {
  
  //remove commas and periods
  $search = str_replace(',', ' ', $search);
  $search = str_replace('.', '*', $search);

  $query = new JoinAndQuery();
  foreach(preg_split("/\s+/", $search) as $word) {
    if($word != "") {
      $token_query = new LdapOrFilter();
      if(strlen($word) == 1) {
        //handle the special case of first initials
        $query_word = "$word*";

        //check for first or middle initial
	$token_query->_OR("cn", "$word*");

        /*****************************************************
         * an ugly hack which forces ldap not to ignore spaces
         *****************************************************/         
        for($cnt = 0; $cnt < 26; $cnt++) {
          $chr = chr(ord('a') + $cnt);
          $token_query->_OR("cn", "*$chr $word*");
        } 
        $token_query->_OR("cn", "*-$word*");

      } else {
        $query_word = "*$word*";
      }
      $token_query
        ->_OR('cn', $query_word)
        ->_OR('mail', $query_word);

      //remove all non-digits from phone number before 
      //attempting phone number searches
      $phone_word = '*' . preg_replace('/\D/', '', $word) . '*';
      if(strlen($phone_word) >= 5) {
        $token_query
          ->_OR('homephone', $phone_word)
          ->_OR('telephonenumber', $phone_word)
          ->_OR('facsimiletelephonenumber', $phone_word);
      } 
      $query->_AND($token_query);
    }
  }
  return $query;
}

function do_query($query, $search_results=array()) {
    $ds = ldap_connect(SERVER) or ldap_die("cannot connect");

    //turn off php Warnings, during ldap search
    //since it complains about search that go over the limit of 100
    $error_reporting = ini_get('error_reporting');
    error_reporting($error_reporting & ~E_WARNING);
    $sr = ldap_search($ds, "dc=mit, dc=edu", $query->out())
         or ldap_die("could not search");
    error_reporting($error_reporting);

    $entries = ldap_get_entries($ds, $sr)
         or ldap_die("could not get entries");

    for ($i = 0; $i < $entries["count"]; $i++) {
         $entry = $entries[$i];

         //some ldap entries have no usefull information
         //we dont want to return those
         if(lkey($entry, "sn", True)) { 
           //if one person has multiple ldap records
           //this code attempts to combine the data in the records
           if($old = $search_results[id_key($entry)]) {
           } else {
             $old = array();
           }
           $search_results[id_key($entry)] = array_merge($old, $entry);
         }
    }

    return $search_results;
}


/*********************************************
 *
 *  this function compares people by firstname then lastname
 *
 *********************************************/
function compare_people($person1, $person2) {
  if($person1['surname'] != $person2['surname']) {
    return ($person1['surname'] < $person2['surname']) ? -1 : 1;
  } elseif($person1['givenname'] == $person2['givenname']) {
    return 0;
  } else {
    return ($person1['givenname'] < $person2['givenname']) ? -1 : 1;
  }
}

    
function lookup_username($id) {
  if(strstr($id, '=')) {
 
    //look up person by "dn" (distinct ldap name)
    $ds = ldap_connect(SERVER);
    $sr = ldap_read($ds, $id, "(objectclass=*)");
    $entries = ldap_get_entries($ds, $sr);
    return make_person($entries[0]);
  } else {
    $tmp = do_query(new QueryElement("uid", $id));
    foreach($tmp as $key => $first) {
      return make_person($first);
    }
  }
}

function lkey($array, $key, $single=False) {
  if($single) {
    return $array[$key][0];
  } else {
    $result = $array[$key];
    if($result === NULL) {
      return array();
    }
    unset($result["count"]);
    return $result;
  }
}

function id_key($info) {
  if($username = lkey($info, "uid", True)) {
    return $username;
  } else {
    return $info["dn"];
  }
}

function make_person($info) {
  $person = array(
     "surname"     => lkey($info, "sn"),
     "givenname"   => lkey($info, "givenname"),
     "fullname"    => lkey($info, "cn"),
     "title"       => lkey($info, "title"),
     "dept"        => lkey($info, "ou"),
     "affiliation" => lkey($info, "edupersonaffiliation"),
     "address"     => lkey($info, "street"),
     "homephone"   => lkey($info, "homephone"),
     "email"       => lkey($info, "mail"),
     "room"        => lkey($info, "roomnumber"),
     "id"          => id_key($info),
     "telephone"   => lkey($info, "telephonenumber"),
     "fax"         => lkey($info, "facsimiletelephonenumber"),
     "office"      => lkey($info, "physicaldeliveryofficename")
  );
  
  foreach($person["office"] as $office) {
    if(!in_array($office, $person["room"])) {
      $person["room"][] = $office;
    }
  }

  return $person;
}  


/**                                                  *
 *  a series of classes alowing for the construction *
 *  of ldap queries                                  *
 *                                                   */
abstract class LdapQuery {
    abstract public function out();

    public static function escape($str) {
        $specials = array("*", "+", "=" , ",");
        foreach($specials as $special) {
            $str = str_replace($special, "\\" . $special, $str);
        }
        return $str;
    }
}

class LdapQueryList extends LdapQuery {

    protected $symbol;
    protected $queries=array();

    public function out() {
        $out = '(' . $this->symbol;
        foreach($this->queries as $query) {
	    $out .= $query->out();
        }
        $out .= ')';
        return $out;
    }
}   
    
class QueryElementList extends LdapQueryList {
    public function __construct($cond_arr=array()) {
        foreach($cond_arr as $field => $value) {
	    $this->add($field, $value);
        }
    }

    public function add($field, $value) {
         $this->queries[] = new QueryElement($field, $value);
         return $this;
    }
}

class LdapAndFilter extends QueryElementList {
    protected $symbol = '&'; 

    public function _AND($field, $value) {
        return $this->add($field, $value);
    }
}

class LdapOrFilter extends QueryElementList {
    protected $symbol = '|'; 

    public function _OR($field, $value) {
        return $this->add($field, $value);
    }
}

class JoinQuery extends LdapQueryList {

  public function __construct() {
     $this->queries = func_get_args();
  }
}

class JoinAndQuery extends JoinQuery {
  protected $symbol = '&';

  public function _AND(LdapQuery $query) {
        $this->queries[] = $query;
        return $this;
  }
}

class JoinOrQuery extends JoinQuery {
  protected $symbol = '|';

  public function _OR(LdapQuery $query) {
        $this->queries[] = $query;
        return $this;
  }
}


class QueryElement extends LdapQuery {
  protected $field;
  protected $value;
    
  static private $special_chars = array( '(', ')' );

  public function __construct($field, $value) {
    $this->field = $field;
   
    //convert all multiple wildcards to a single wildcard
    $this->value = preg_replace('/\*+/', '*', $value);
  }
  
  public function out() {
    $escaped_value = $this->value;
    $escaped_value = str_replace("\\", "\\\\", $escaped_value);
    foreach(self::$special_chars as $char) {
      $escaped_value = str_replace($char, "\\" . $char, $escaped_value);
    
    }
    return '(' . $this->field . '=' . $escaped_value . ')';
  }
}

class RawQuery extends LdapQuery {
  protected $raw_query;
  
  public function __construct($raw_query) {
    $this->raw_query = $raw_query;
  }

  public function out() {
    return $this->raw_query;
  }
}  

function ldap_die($message) {
  throw new DataServerException($message);
}

?>