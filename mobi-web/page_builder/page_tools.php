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

  public function prev_next_html($url, $params, $id_name) {
    $arrows = new HtmlPrevNextArrows($url, $params, $id_name, $this->prev_id(), $this->next_id());
    return $arrows->prev_next_html();
  }      
}

class HtmlPrevNextArrows {

  public function __construct($url, $params, $id_name, $prev_id, $next_id) {
    $this->url = $url;
    $this->params = $params;
    $this->id_name = $id_name;
    $this->prev_id = $prev_id;
    $this->next_id = $next_id;
  }

  private function next_html() {
    if($this->next_id === NULL) {
      return '';
    }

    $params = $this->params;
    $params[$this->id_name] = $this->next_id;
    $url = $this->url . '?' . http_build_query($params);
    
    return '<a href="' . $url . '">Next &gt;</a>';
  }

  private function prev_html() {
    if($this->prev_id === NULL) {
      return '';
    }

    $params = $this->params;
    $params[$this->id_name] = $this->prev_id;
    $url = $this->url . '?' . http_build_query($params);
    
    return '<a href="' . $url . '">&lt; Prev</a>';
  }

  public function prev_next_html() {
    $next = $this->next_html();
    $prev = $this->prev_html();
    $middle = ($next && $prev) ? "&nbsp;|&nbsp;" : "";
    return $prev . $middle . $next;
  }
}  

class Tabs {
  
  private $url;
  private $tabs;
  private $active;
  private $param;
  
  public function __construct($url, $param, $tabs) {
    $this->url = $url;
    $this->tabs = $tabs;
    $this->active = $_REQUEST[$param];
    $this->param = $param;
  }

  public function hide($tab) {
    $index = array_search($tab, $this->tabs);
    $this->tabs = array_merge(
      array_slice($this->tabs, 0, $index),
      array_slice($this->tabs, $index+1)
    );
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
	  '<span class="active">' . $tab . '</span>' :
	  '<a class="active">' . $tab . '</a>';
      } else {
	  $html[] = "<a href=\"$url\">$tab</a>";
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

  public function __construct($template, $module, $page, $extra_params = array()) {
    $this->template = $template;
    $this->module = $module;
    $this->extra_params = $extra_params;
    $this->max_list_items = $page->max_list_items;
    $this->branch = $page->branch;
    $this->form = new StandardForm($this->branch);
  }
   
  public function set_form(Form $form) {
    $this->form = $form;
    return $this;
  }

  public function output($results) {
  
    $total = count($results);

    //truncate results and determine if a next page is needed
    $start = $_REQUEST["start"] ? (int)$_REQUEST["start"] : 0;
    $pager = new Pager($this->max_list_items, $results, $start);
    $results = $pager->items();

    $search_terms = $_REQUEST['filter'];
    $params = array_merge(array("filter" => $search_terms), $this->extra_params);
    $arrows = $pager->prev_next_html($_SERVER['SCRIPT_NAME'], $params, "start");

    $start++;
    $end = $pager->last();


    require "../{$this->branch}/search_results.html";
  }
}
 
abstract class Form {

  protected $prefix;

  public function __construct($prefix) {
    $this->prefix = $prefix;
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

  $text = htmlentities(substr($text, 0, MAX_TEXT), ENT_QUOTES, 'UTF-8');
  if($dots) {
    $text .= '...';
  }
  return $text;
}

function is_long_string($text) {
  $temp = trim($text);
  return strlen($temp) > MAX_TEXT;
}

function google_analytics($bucket) {
  switch($bucket) {
  case "Webkit":
    $id = "UA-381327-6";
    break;

  default:
    $id = "UA-381327-5";
    break;
  }
  
  if(!$id) {
    return "";
  }
  
  $javascript =<<<JS
   <script type="text/javascript">
     var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
     document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
   </script>
   <script type="text/javascript">
    try {
      var pageTracker = _gat._getTracker("$id");
      pageTracker._trackPageview();
    } catch(err) {}</script>
JS;
  return $javascript;
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
  preg_match('/^((|W|N|E|NW|NE)\-?(\d+))/', $location, $matches);
  if($matches[3]) {
    return "../map/detail.php?selectvalues={$matches[2]}{$matches[3]}&snippets=$location";
  } else {
    return "../map/search.php?filter=" . urlencode($location);
  }
}

class HTMLFragment {
  private $dom_document;
  private static $html_header = '<html><meta http-equiv="Content-Type" content="text/html;charset=utf-8"><body>';
  private static $html_footer = "</body>\n</html>";

  public function __construct($html) {
    $this->dom_document = new DOMDocument();
    $this->dom_document->loadHTML(self::$html_header . $html . self::$html_footer);    
  }
    
  public function getBody() {
    $full_html = $this->dom_document->saveHTML();
    $body_tag_pos = strpos($full_html, '<body>') + strlen('<body>');
    $inner_fragment = substr($full_html, $body_tag_pos, -strlen(self::$html_footer)-1);
    return $inner_fragment;
  }
    
  public function bodyElement() {
    return $this->dom_document->getElementsByTagname('body')->item(0);
  }
  
  /**
   *  uses the strategy of estimating paragraphs based on linebreaks "<br />"
   */
  public function pages() {
    $body = $this->dom_document->getElementsByTagname('body')->item(0);

    $current_page = new HTMLFragment("");
    $current_page_count = 0;
    $current_linebreak_count = 0;
    $pages = array($current_page);
    foreach($this->bodyElement()->childNodes as $node) {
      if($node->nodeName == 'br') {
        $current_linebreak_count += 1;
      }
      if($node->nodeName == 'p') {
        // a new paragraph is similar to two linebreaks
        $current_linebreak_count += 2;
      }

      $nodeCopy = $current_page->dom_document->importNode($node, TRUE);
      $current_page->bodyElement()->appendChild($nodeCopy);
        
      if($current_linebreak_count >= 12) {
        // we limit the number of paragraphs per page
        $current_page = new HTMLFragment("");
	$current_page_count = 0;
	$current_linebreak_count = 0;
	$pages[] = $current_page;
      }
    }
    
    // trim white spaces and line breaks
    $trimmed_pages = array();
    foreach($pages as $page) {
      $trimmed_page = $page->trim();
      if($trimmed_page) {
        $trimmed_pages[] = $trimmed_page;
      }
    }
    return $trimmed_pages;
  }

  public function trim() {
    $new = new HTMLFragment($this->getBody());
    $body = $new->bodyElement();    

    // trim beginning
    while($body->firstChild && self::is_white_space($body->firstChild)) {
      $body->removeChild($body->firstChild);
    }
    if($body->firstChild && $body->firstChild->nodeName == '#text') {
      $body->firstChild->nodeValue = ltrim($body->firstChild->nodeValue);
    }

    // trim end
    while($body->lastChild && self::is_white_space($body->lastChild)) {
      $body->removeChild($body->lastChild);
    }
    if($body->lastChild && $body->lastChild->nodeName == '#text') {
      $body->lastChild->nodeValue = rtrim($body->lastChild->nodeValue);
    }

    // only return pages with non empty text
    if(trim($new->getBody())) {
      return $new;
    }
  }

  private function is_white_space($domNode) {
    if( ($domNode->nodeName == '#text') && (trim($domNode->nodeValue) == '') ) {
      return True;
    }

    if($domNode->nodeName == 'br') {
      return True;
    }

    return False;
  }
}

?>