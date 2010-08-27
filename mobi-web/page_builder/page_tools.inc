<?php

/* maximum length of text description before it gets shortened to "... more" */
define('MAX_TEXT', 80); 

abstract class DrillDownList {
  
  protected $lower;
  protected $upper;
  protected $keys_or_values;

  public function __construct($limits_str, $type='value') {
    $limits = explode('-', strtolower($limits_str));
    $this->lower = $limits[0];
    $this->upper = $limits[1];
    $this->keys_or_values = !($type == 'value');
  }

  public function get_list($items) { 
    $sublist = array();
    foreach($items as $key => $value) {
      $filtered = $this->keys_or_values ? $key : $value; 
      if($this->isBetween($filtered)) {
	  $sublist[$key] = $value;
      } 
    }
    return $sublist;
  }

  protected function isBetween($target) {
    $target = strtolower($target);
    if($this->upper) {
      return $this->isBetween2($target);
    } else {
      return preg_match("/^{$this->lower}/", $target);
    }
  }

  abstract protected function isBetween2($target);
}

class DrillAlphabeta extends DrillDownList {
  protected function isBetween2($target) {
    return (($this->lower < $target) && ($target < $this->upper))
      || preg_match("/^{$this->lower}/", $target)
      || preg_match("/^{$this->upper}/", $target);
  }
}

class DrillNumeralAlpha extends DrillDownList {
  protected function isBetween2($target) {
    preg_match("/^([a-z]*)(\d+)/",$this->lower, $bottom_match);
    preg_match("/^([a-z]*)(\d+)/",$this->upper, $top_match);
    preg_match("/^([a-z]*)(\d+)/",$target, $target_match);

    if($target_match[1] > $bottom_match[1] &&
       $target_match[1] < $bottom_match[1]) {
      return true;
    }

    if($bottom_match[1] == $top_match[1]) {
      if($target_match[1] != $bottom_match[1]) {
        return false;
      } else {
        return ((int) $target_match[2] >= (int) $bottom_match[2])
	  && ((int) $target_match[2] <= (int) $top_match[2]);
      }
    }
      
    if($target_match[1] == $bottom_match[1] &&
       (int)$target_match[2] >= (int)$bottom_match[2]) {
      return true;
    }

    if($target_match[1] == $top_match[1] &&
       (int)$target_match[2] <= (int)$top_match[2]) {
      return true;
    }
  }
}

class Pager {
  private $items;
  private $start; 
  private $end;
  private $limit;

  function __construct($limit, $items, $start=0) {
    $this->limit = $limit;
    $this->items = $items;
    $this->start = $start;
    if ($this->limit) {
      $this->end = min($this->limit + $start, count($items));
    } else {
      $this->end = count($items);
    }
  }
  
  public function last() {
    return $this->end;
  }

  public function items() {
    $length = $this->end - $this->start;
    return array_slice($this->items, $this->start, $length);
  }

  public function prev_id() {
    if($this->start == 0) {
      return NULL;
    }

    if($this->limit === NULL) {
      return 0;
    }

    $prev_id = $this->start - $this->limit;

    return ($prev_id >= 0) ? $prev_id : 0;
  }
    
  public function next_id() {
    if($this->end == count($this->items)) {
      return NULL;
    } else {
      return $this->end;
    }
  }

  private function next_url($url, $params, $id_name) {
    if($this->next_id() === NULL) {
      return '';
    }

    $params[$id_name] = $this->next_id();
    $url .= '?' . http_build_query($params);
    return $url;
  }

  private function prev_url($url, $params, $id_name) {
    if($this->prev_id() === NULL) {
      return '';
    }

    $params[$id_name] = $this->prev_id();
    $url .= '?' . http_build_query($params);
    return $url;
  }

  public function prev_next_html($url, $params, $id_name) {
    $next_url = $this->next_url($url, $params, $id_name);
    $prev_url = $this->prev_url($url, $params, $id_name);
    return html_arrows($prev_url, "Prev", $next_url, "Next");
  }
}  

function html_arrows($previous_url, $previous_label, $next_url, $next_label) {
   $previous_link = NULL;
   if($previous_url) {
        $previous_link =  '<a href="' . $previous_url . '">&lt; ' . $previous_label . '</a>';
   }

   $next_link = NULL;
   if($next_url) {
        $next_link =  '<a href="' . $next_url . '">' . $next_label . ' &gt;</a>';
   }

   $middle = ($next_url && $previous_url) ? "&nbsp;|&nbsp;" : "";

   return $previous_link . $middle . $next_link;
}

class Tabs {
  
  private $url;
  private $tabs;
  private $active;
  private $param;
  
  public function __construct($url, $param, $tabs) {
    $this->url = $url;
    $this->tabs = $tabs;
    $this->active = isset($_REQUEST[$param]) ? $_REQUEST[$param] : null;
    $this->param = $param;
  }

  public function hide($tab) {
    $index = array_search($tab, $this->tabs);
    $this->tabs = array_merge(
      array_slice($this->tabs, 0, $index),
      array_slice($this->tabs, $index+1)
    );
  }

  /*
   * This method allows the initial "open" tab to 
   * be different than the first tab
   */
  public function setDefaultActive($tab) {
    if(!$this->active) {
        $this->active = $tab;
    }
  }

  public function active() {
    if($this->active) {
      return $this->active;
    } else {
      return $this->tabs[0];
    }
  }

  public function html($branch) {
    $html = array();
    foreach($this->tabs as $index => $tab) {
      $url = $this->url . "&" . $this->param . "=" .urlencode($tab);
      if($tab == $this->active()) {
	$html[] = ($branch == 'Basic') ? 
	  '<span class="active">' . ucwords($tab) . '</span>' :
	  '<a class="active">' . ucwords($tab) . '</a>';
      } else {
	  $html[] = "<a href=\"$url\">" . ucwords($tab) . "</a>";
      }
    }
    if ($branch == 'Basic') {
      return implode("&nbsp;|&nbsp;", $html);
    } else {
      return implode("\n", $html);
    }
  }
}

class ResultsContent {
  private $form;

  private $branch;

  // the KEYWORD which appears in the pages title
  private $title;

  // the file the contains the formating of the output list
  private $template;
 
  // the module where the list template can be found
  private $module;

  // extra parameters needed for the next and previous arrows
  private $extra_params;

  public function __construct($template, $module, $page, $extra_params = array(), $showForm = TRUE) {
    $this->template = $template;
    $this->module = $module;
    $this->extra_params = $extra_params;
    $this->max_list_items = isset($page->max_list_items) ? $page->max_list_items : NULL;
    $this->branch = $page->branch;
	if ($showForm) {	
		$this->form = new StandardForm($this->branch, $page->platform);
	}
  }
   
  public function set_form(Form $form) {
    $this->form = $form;
    return $this;
  }

  public function output($results) {
  
    $total = count($results);

    //truncate results and determine if a next page is needed
    $start = isset($_REQUEST["start"]) ? (int)$_REQUEST["start"] : 0;
    $pager = new Pager($this->max_list_items, $results, $start);
    $results = $pager->items();

    $search_terms = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : "";
    $params = array_merge(array("filter" => $search_terms), $this->extra_params);
    $arrows = $pager->prev_next_html($_SERVER['SCRIPT_NAME'], $params, "start");

    $start++;
    $end = $pager->last();


    require "../{$this->branch}/search_results.html";
  }
}
 
abstract class Form {

  protected $prefix;
  protected $platform;

  public function __construct($prefix, $platform) {
    $this->prefix = $prefix;
    $this->platform = $platform;
  }

  abstract public function out();
}

class StandardForm extends Form {

  public function out($total=NULL) {
    require "../{$this->prefix}/form.html";
  }
}

function short_date($date) {
  $minute = $date['minute'];
  $minute = $minute < 10 ? '0' . $minute : (string) $minute;
  return "{$date['month']}/{$date['day']} {$date['hour']}:$minute";
}


function summary_string($text) {

  //check if the last character or
  //the character after last is not a word character
  if( preg_match('/\W/', substr($text, MAX_TEXT-1, 2)) ) {
    $dots = False;
  } else {
    $dots = True;
  }

  $text = htmlentities(substr($text, 0, MAX_TEXT));
  if($dots) {
    $text .= '...';
  }

  //this hack fixes some strange encoding problem
  return str_replace('&Acirc;', '', $text);
}

function is_long_string($text) {
  $temp = trim($text);
  return strlen($temp) > MAX_TEXT;
}

// function that inserts silent hyphens for chunks of text that 
// old Palm Pre firmware thinks are phone numbers
function pre_hack($text) {
  return preg_replace('/([\d\-\. ]{4})([\d\-\. ]+)/', '${1}&shy;${2}', $text);
}

function notPhoneNumber($text) {
  $output = '';
  for($i = 0; $i < strlen($text); $i++) {
    $output .= '<span>'. substr($text,$i, 1) . '</span>';
  }
  return $output;
}

// create a URL to the campus map if given a building number
// or a search term
function mapURL($location) {
  return "../map/search.php?filter=" . urlencode($location);
}

// create URL to the campus map for a courses lookup
function mapURLForCourses($location) {
  return "../map/search.php?loc=courses&filter=" . urlencode($location);
}


class HTMLPage {
    private $document;
    private $body;
    private static $header = "<html><body>";
    private static $footer = "</body></html>";


    public function __construct() {
        $this->document = new DOMDocument();
        $this->document->loadHTML(self::$header . self::$footer);
        $root = $this->document;
        $this->body = $root->getElementsByTagName("body")->item(0);
    }

    public function addNode(DOMNode $node) {
        $nodeCopy = $this->document->importNode($node, true);
        $this->body->appendChild($nodeCopy);
    }

    public function getText() {
        $text = $this->document->saveHTML();

        // removes header
        $content_position = strpos($text, self::$header) + strlen(self::$header);
        $text = substr($text, $content_position);

        // removes footer
        $text = substr($text, 0, strlen($text) - strlen(self::$footer)-1);

        return trim($text);
    }

}

function HTMLPager($html, $paragraph_limit) {
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $body = $dom->getElementsByTagName("body")->item(0);

    $current_page = NULL;
    $pages = array();
    $current_paragraph_count = 0;

    foreach($body->childNodes as $node) {
        if($current_page == NULL) {
            // need to start a new page
            if(($node->nodeName == "#text") && (trim($node->nodeValue) == "")) {
                // this node is blank so do not start a new page yet
                continue;
            }

            $current_page = new HTMLPage();
            $pages[] = $current_page;
        }

        $current_page->addNode($node);

        if($node->nodeName == "p") {
            $current_paragraph_count++;
        }

        if($current_paragraph_count == $paragraph_limit) {
            $current_page = NULL;
            $current_paragraph_count = 0;
        }
    }

    return $pages;
}

?>
