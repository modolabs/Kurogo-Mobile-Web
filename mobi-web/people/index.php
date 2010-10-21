<?php
    $docRoot = getenv("DOCUMENT_ROOT");

    require_once $docRoot . "/mobi-config/mobi_web_constants.php";
    require WEBROOT . "page_builder/page_header.php";
    require LIBDIR . "error_logging.php";
    require LIBDIR . "mit_ldap.php";

    $errorMessage = null;
    $failed_search = FALSE;
    init_logging();
    $search_terms = $_REQUEST["filter"];
    if (($search_terms == null) || (strlen($search_terms) == 0))
    {
        $search_terms = "";
    }
    $search_terms = trim($search_terms);

    if (isset($_REQUEST["username"]))
    {
        $person = lookup_username($_REQUEST["username"]);
        if ($appError == 0)
        {
            $person = html_escape_person($person);
            require "$page->branch/detail.html";
        }
        else
        {
            $errorMessage = $person;
            require "$page->branch/index.html";
        }
    }
    elseif ($search_terms)
    {
        //search mit ldap directory
        $people = mit_search($search_terms);
        if ($appError != 1)
        {
            $people = html_escape_people($people);
            $total = count($people);
            if ($total == 0)
            {
                $failed_search = True;
                require "$page->branch/index.html";
            }
            elseif ($total == 1)
            {
                $person = $people[0];
                require "$page->branch/detail.html";
            }
            else
            {
                $content = new ResultsContent("items", "people", $page);
                require "$page->branch/results.html";
            }
        }
        else
        {
            $errorMessage = $people;
            require "$page->branch/index.html";
        }
    }
    else
    {
        $page->cache();
        require "$page->branch/index.html";
    }

    $page->output();

function detail_url($person)
{
    return $_SERVER['SCRIPT_NAME'] . '?username=' . urlencode($person["id"]) . '&filter=' . urlencode($_REQUEST['filter']);
}

function phoneHREF($number)
{
    return 'tel:1' . str_replace('-', '', $number);
}

function mailHREF($email)
{
    return "mailto:$email";
}

//function mapHREF($place) {
//  preg_match("/^[A-Z]*\d+[A-Z]*/", $place, $match);
//  return "../map/detail.php?selectvalues=" .  $match[0];
//}

function html_escape_people($people)
{
    foreach($people as $index => $person)
    {
        $people[$index] = html_escape_person($person);
    }
    return $people;
}

function html_escape_person($person)
{
    foreach($person as $att => $values)
    {
        if($att != "id")
        {
            foreach($values as $index => $value)
            {
                $person[$att][$index] = ldap_decode(htmlentities($value));
            }
        }
    }
    return $person;
}

function has_phone($person)
{
   return (count($person['homephone']) > 0) || 
          (count($person['phone']) > 0) ||
          (count($person['fax']) > 0); 
}

function ldap_decode($ldap_str)
{
    return preg_replace_callback("/0x(\d|[A-F]){4}/", "unicode2utf8", $ldap_str);
}

function unicode2utf8($match_array)
{
    $c = hexdec($match_array[0]);
    if($c < 0x80)
    {
        return chr($c);
    }
    else if($c < 0x800)
    {
        return chr( 0xc0 | ($c >> 6) ).chr( 0x80 | ($c & 0x3f) );
    }
    else if($c < 0x10000)
    {
        return chr( 0xe0 | ($c >> 12) ).chr( 0x80 | (($c >> 6) & 0x3f) ).chr( 0x80 | ($c & 0x3f) );
    }
    else if($c < 0x200000)
    {
        return chr(0xf0 | ($c >> 18)).chr(0x80 | (($c >> 12) & 0x3f)).chr(0x80 | (($c >> 6) & 0x3f)).chr(0x80 | ($c & 0x3f));
    }
    return false;
}
?>
