<?php
define("DIRECTORY_UNAVAILABLE", "Your request cannot be processed at this time.\nPlease try again later....");
define("INVALID_TELEPHONE_NUMBER", "Invalid telephone number....");
define("INVALID_EMAIL_ADDRESS","Invalid email address....");

function mit_search($search)
{
    global $appError;

    $query = standard_query($search);
    if ($appError == 1)
        return($query);
    $results = do_query($query);
    if ($appError == 1)
        return($results);
    return(order_results($results, $search));
}

function order_results($results, $search)
{
    $low_priority = array();
    $high_priority = array();
    foreach($results as $result)
    {
        $item = make_person($result);
        if(has_priority($item, $search))
        {
            $high_priority[] = $item;
        } 
        else
        {
            $low_priority[] = $item;
        }  
    }
    //Alphabetize low_priority array
    usort($low_priority, "compare_people");
    return array_merge($high_priority, $low_priority);
}

function has_priority($item, $search)
{
    $words = preg_split('/\s+/', trim($search));
    if(count($words) == 1)
    {
        $word = strtolower($words[0]);
        $emails = $item['email'];
        foreach($emails as $email)
        {
            $email = strtolower($email);
            if( ($email == $word) || (substr($email, 0, strlen($word)+1) == "$word@") )
            {
                return True;
            }
        }
    }
    return False;
}
/*
function email_query($search)
{
    $words = preg_split('/\s+/', trim($search));
    if(count($words) == 1)
    {
        $word = $words[0];
        if(strpos($words, '@') === False)
        {
            //turns blpatt into blpatt@*
            $word .= '@*';
        }
        return new QueryElement('mail', $word);
    }
}
*/

function standard_query($search)
{
    global $appError;

    if (strpos($search, "@") != FALSE)
    {
        if (strpos($search, " ") != FALSE)
        {
            $appError = 1;
            return(INVALID_EMAIL_ADDRESS);
        }
        $emailFilter = EMAIL_FILTER;
        $emailFilter = str_replace("%s", $search, $emailFilter);
        $searchFilter = EMAIL_SEARCH_FILTER;
        $searchFilter = str_replace("%s", $emailFilter, $searchFilter);
        return($searchFilter);
    }
    if (strpbrk(strtolower($search), "abcdefghijklmnopqrstuvwxyz") != FALSE)
    {
        $nameFilter = "";
        foreach(preg_split("/\s+/", $search) as $word)
        {
            if($word != "")
            {
                if(strlen($word) == 1)
                {
                    $Filter = NAME_SINGLE_CHARACTER_FILTER;
                    $nameFilter = $nameFilter . str_replace("%s", $word, $Filter);
                }
                else
                {
                    $Filter = NAME_MULTI_CHARACTER_FILTER;
                    $nameFilter = $nameFilter . str_replace("%s", $word, $Filter);
                }
            }
        }
        $searchFilter = NAME_SEARCH_FILTER;
        $searchFilter = str_replace("%s", $nameFilter, $searchFilter);
        return($searchFilter);
    }
    $search = str_replace("(", "", $search);
    $search = str_replace(")", "", $search);
    $search = str_replace(" ", "", $search);
    $search = str_replace(".", "", $search);
    $search = str_replace("-", "", $search);
    if (($search == null) || (strlen($search) == 0))
    {
        $appError = 1;
        return(INVALID_TELEPHONE_NUMBER);
    }
    $telephoneFilter = TELEPHONE_FILTER;
    $telephoneFilter = str_replace("%s", $search, $telephoneFilter);
    $searchFilter = TELEPHONE_SEARCH_FILTER;
    $searchFilter = str_replace("%s", $telephoneFilter, $searchFilter);
    return($searchFilter);
}

function do_query($query, $search_results=array())
{
    global $appError;
    
    try
    {
        $ds = ldap_connect(LDAP_SERVER);
        if ($ds == FALSE)
        {
            throw new Exception("");
        }
        //turn off php Warnings, during ldap search
        //since it complains about search that go over the limit of 100
        $error_reporting = ini_get('error_reporting');
        error_reporting($error_reporting & ~E_WARNING);
        // set a 10 second timelimit
        $sr = ldap_search($ds, "dc=mit, dc=edu", $query, array(), 0, 0, SEARCH_TIMELIMIT);
        if ($sr == FALSE)
        {
            throw new Exception("");
        }
        error_reporting($error_reporting);
        $entries = ldap_get_entries($ds, $sr);
        if ($entries == FALSE)
        {
            throw new Exception("");
        }
    }
    catch (Exception $e)
    {
        $appError = 1;
        log_error($php_errormsg);
        return(DIRECTORY_UNAVAILABLE);
    }
    for ($i = 0; $i < $entries["count"]; $i++)
    {
        $entry = $entries[$i];
        //some ldap entries have no usefull information
        //we dont want to return those
        if(lkey($entry, "sn", True))
        {
            //if one person has multiple ldap records
            //this code attempts to combine the data in the records
            if($old = $search_results[id_key($entry)])
            {
            }
            else
            {
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
function compare_people($person1, $person2)
{
    if($person1['surname'] != $person2['surname'])
    {
        return ($person1['surname'] < $person2['surname']) ? -1 : 1;
    }
    elseif($person1['givenname'] == $person2['givenname'])
    {
        return 0;
    }
    else
    {
        return ($person1['givenname'] < $person2['givenname']) ? -1 : 1;
    }
}
    
function lookup_username($id)
{
    global $appError;

    if(strstr($id, '='))
    {
        //look up person by "dn" (distinct ldap name)
        try
        {
            $ds = ldap_connect(LDAP_SERVER);
            if ($ds == FALSE)
            {
                throw new Exception("");
            }
            // set a 10 second timelimit
            $sr = ldap_read($ds, $id, "(objectclass=*)", array(), 0, 0, READ_TIMELIMIT);
//            $sr = ldap_read($ds, $id, "(objectclass=*)");
            if ($sr == FALSE)
            {
                throw new Exception("");
            }
            $entries = ldap_get_entries($ds, $sr);
            if ($entries == FALSE)
            {
                throw new Exception("");
            }
            return make_person($entries[0]);
        }
        catch (Exception $e)
        {
            $appError = 1;
            return(DIRECTORY_UNAVAILABLE);
        }
    }
    else
    {
        $uidFilter = UID_FILTER;
        $uidFilter = str_replace("%s", $id, $uidFilter);
        $searchFilter = UID_SEARCH_FILTER;
        $searchFilter = str_replace("%s", $uidFilter, $searchFilter);
        $tmp = do_query($searchFilter);
        if ($appError == 1)
            return($tmp);
        foreach($tmp as $key => $first)
        {
            return make_person($first);
        }
    }
}

function lkey($array, $key, $single=False)
{
    if ($single)
    {
        return $array[$key][0];
    }
    else
    {
        $result = $array[$key];
        if($result === NULL)
        {
            return array();
        }
        unset($result["count"]);
        return $result;
    }
}

function id_key($info)
{
    if($username = lkey($info, "uid", True))
    {
        return $username;
    }
    else
    {
        return $info["dn"];
    }
}

function make_person($info)
{
    global $personDisplayMapping;
    
    $person = array();
    foreach ($personDisplayMapping as $personDisplay)
    {
        if (($personDisplay[4] == TRUE) || ($personDisplay[7] == TRUE))
        {
            if (strcasecmp($personDisplay[0], "id") == 0)
            {
                $person[$personDisplay[0]] = id_key($info);
            }
            else
            {
                $person[$personDisplay[0]] = lkey($info, $personDisplay[1]);
            }
        }
    }
/*
  $person = array(
     $personDisplayMapping[0][0]=>lkey($info, $personDisplayMapping[0][1]),
     $personDisplayMapping[1][0]=>lkey($info, $personDisplayMapping[1][1]),
     $personDisplayMapping[2][0]=>lkey($info, $personDisplayMapping[2][1]),
     $personDisplayMapping[3][0]=>lkey($info, $personDisplayMapping[3][1]),
     $personDisplayMapping[4][0]=>lkey($info, $personDisplayMapping[4][1]),
     $personDisplayMapping[5][0]=>lkey($info, $personDisplayMapping[5][1]),
     $personDisplayMapping[6][0]=>lkey($info, $personDisplayMapping[6][1]),
     $personDisplayMapping[7][0]=>lkey($info, $personDisplayMapping[7][1]),
     $personDisplayMapping[8][0]=>lkey($info, $personDisplayMapping[8][1]),
     $personDisplayMapping[9][0]=>lkey($info, $personDisplayMapping[9][1]),
     $personDisplayMapping[10][0]=>lkey($info, $personDisplayMapping[10][1]),
     $personDisplayMapping[11][0]=>lkey($info, $personDisplayMapping[11][1]),
     $personDisplayMapping[12][0]=>lkey($info, $personDisplayMapping[12][1]),
     $personDisplayMapping[13][0]=>lkey($info, $personDisplayMapping[13][1])
  );
*/
    foreach($person["room"] as $room)
    {
        if(!in_array($room, $person["office"]))
        {
            $person["office"][] = $room;
        }
    }
    if ($person['givenname'] != null)
    {
        if ($person["initials"] != null)
        {
            $person["givenname"][0] = $person["givenname"][0] . " " . $person["initials"][0];
        }
    }
    unset($person["initials"]);
    unset($person["room"]);
    unset($person["count"]);

    return $person;
}  


/**                                                  *
 *  a series of classes alowing for the construction *
 *  of ldap queries                                  *
 *                                                   */
abstract class LdapQuery
{
    abstract public function out();

    public static function escape($str)
    {
        $specials = array("*", "+", "=" , ",");
        foreach($specials as $special)
        {
            $str = str_replace($special, "\\" . $special, $str);
        }
        return $str;
    }
}

class LdapQueryList extends LdapQuery
{
    protected $symbol;
    protected $queries=array();

    public function out()
    {
        if ($this->symbol != null)
        {
            $out = '(' . $this->symbol;
        }
        foreach($this->queries as $query)
        {
	    $out .= $query->out();
        }
        if ($this->symbol != null)
        {
            $out .= ')';
        }
        return $out;
    }
}   
    
class QueryElementList extends LdapQueryList
{
    public function __construct($cond_arr=array())
    {
        foreach($cond_arr as $field => $value)
        {
	    $this->add($field, $value);
        }
    }

    public function add($field, $value)
    {
         $this->queries[] = new QueryElement($field, $value);
         return $this;
    }
}

class LdapFilter extends QueryElementList
{

    public function _($field, $value)
    {
        return $this->add($field, $value);
    }
}

class LdapAndFilter extends QueryElementList
{
    protected $symbol = '&';

    public function _AND($field, $value)
    {
        return $this->add($field, $value);
    }
}

class LdapOrFilter extends QueryElementList
{
    protected $symbol = '|'; 

    public function _OR($field, $value)
    {
        return $this->add($field, $value);
    }
}

class JoinQuery extends LdapQueryList
{

    public function __construct()
    {
        $this->queries = func_get_args();
    }
}

class JoinAndQuery extends JoinQuery
{
    protected $symbol = '&';

    public function _AND(LdapQuery $query)
    {
        $this->queries[] = $query;
        return $this;
    }
}

class JoinOrQuery extends JoinQuery
{
    protected $symbol = '|';

    public function _OR(LdapQuery $query)
    {
        $this->queries[] = $query;
        return $this;
    }
}

class QueryElement extends LdapQuery
{
    protected $field;
    protected $value;
    
    static private $special_chars = array( '(', ')' );

    public function __construct($field, $value)
    {
        $this->field = $field;
   
        //convert all multiple wildcards to a single wildcard
        $this->value = preg_replace('/\*+/', '*', $value);
    }
  
    public function out()
    {
        $escaped_value = $this->value;
        $escaped_value = str_replace("\\", "\\\\", $escaped_value);
        foreach(self::$special_chars as $char)
        {
            $escaped_value = str_replace($char, "\\" . $char, $escaped_value);
        }
        return '(' . $this->field . '=' . $escaped_value . ')';
    }
}

class RawQuery extends LdapQuery
{
    protected $raw_query;
  
    public function __construct($raw_query)
    {
        $this->raw_query = $raw_query;
    }

    public function out()
    {
        return $this->raw_query;
    }
}  

function ldap_die($message)
{
    throw new DataServerException($message);
}

?>