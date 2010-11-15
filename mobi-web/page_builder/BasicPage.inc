<?php

class BasicPage extends Page {

  protected static $access_key = 1; // feature phone access key
  protected $extra_links = array();
  protected $help_links = array();
  protected $bottom_nav_links = array();
  public $fontsize;

  protected $img_extension;
  protected $viewport_content;
  protected $meta_mobile_optimized;

  public function __construct($platform, $certs) {
    global $ini;

    $this->branch = 'Basic';
    $this->platform = $platform;
    $this->certs = $certs;
    $this->max_list_items = 20;
    include WEBROOT . '/' . $this->delta_file('attribs', 'inc');

    $this->img_extension = $img_extension;
    $this->viewport_content = $viewport_content;
    $this->meta_mobile_optimized = $meta_mobile_optimized;

    // get font size from selection (override cookie value)
    if (isset($_REQUEST['font'])) {
      $fontsize = $_REQUEST['font'];
      setcookie('fontsize', $fontsize, time() + $ini['LAYOUT_COOKIE_LIFESPAN'], '/');      
//      echo "Set cookie to fontsize: $fontsize<br>";
      $this->fontsize = $fontsize;      
            
    } else if (isset($_COOKIE['fontsize'])) { 
                
        $this->fontsize = $_COOKIE['fontsize'];
//        echo "Using fontsize from cookie: $this->fontsize<br>";        
    }
    $cookie_params = "$this->branch,$this->platform,$this->certs";


    switch ($this->fontsize) {
    case 'small':
      $this->inline_css = 'body { font-size: 89%; line-height: 1.33em }';
      break;
    case 'large':
      $this->inline_css = "body { font-size: 125%; line-height: 1.33em } .label { padding-top:0; } .inputcombo .combobutton { padding: .1em .2em; }";
      break;
    case 'xlarge':
      $this->inline_css = 'body { font-size: 150%; line-height: 1.33em }';
      break;
    default:
      $this->inline_css = 'body { font-size: 100%; line-height: 1.33em }';
      break;
    }

    $this->varnames = Array(
      'module', 'header', 'title', 'stylesheets', 'help_on',
      'extra_links', 'help_links', 'bottom_nav_links', 'inline_css',
      'meta_mobile_optimized', 'img_extension', 'viewport_content',
    );
  }
  
  public function googleAnalyticsGetImageUrl() {
    $url = "/Basic/ga.php?";
    $url .= "utmac=" . GA_ACCOUNT;
    $url .= "&utmn=" . rand(0, 0x7fffffff);

    $referer = $_SERVER["HTTP_REFERER"];
    $query = $_SERVER["QUERY_STRING"];
    $path = $_SERVER["REQUEST_URI"];

    if (empty($referer)) {
      $referer = "-";
    }
    $url .= "&utmr=" . urlencode($referer);

    if (!empty($path)) {
      $url .= "&utmp=" . urlencode($path);
    }

    $url .= "&guid=ON";

    return '<img src="'.$url.'" />';
  }

  public function header($header) {
    $this->header = $header;
    return $this;
  }

  public function extra_link($href, $text, $class=NULL) {
    $this->extra_links[] = array("url" => $href, "text" => $text, "class" => $class);
    return $this;
  }

  public function help_link($href, $text, $class=NULL, $phone=NULL) {
    $this->help_links[] = array("url" => $href, "text" => $text, "class" => $class, "phone" => $phone);
    return $this;
  }

  public function nav_link($href, $text) {
    $this->bottom_nav_links[] = array("url" => $href, "text" => $text);
    return $this;
  }

  public function font_selector() {
    $font_html = Array();
    foreach (Array('small', 'medium', 'large', 'xlarge') as $fontsize) {
      if ($this->fontsize == $fontsize) {
	$font_html[] = '<span class="font' .$fontsize . '">A</span>';
      } else {
	$font_html[] = '<a href="index.php?font=' . $fontsize . '" class="font' . $fontsize . '">A</a>';
      }
    }
    return implode(' | ', $font_html);
  }

  // insert a dummy paragraph to fix bb spacing
  public function bbhack() {
    return '<p class="bb"> </p>';
  }

  public function home_header() {
    if ($this->platform == 'bbplus') {
      return '<h1 id="header">'
           . $this->img_tag('harvard-logo-home', 'png')
           . SITE_NAME . '</h1>';
    } else {
      return '<h1>'
           . $this->img_tag('harvard-logo-home', 'gif', '', array('id' => 'logo'))
           . '</h1>';
    }
  }

  public function link_access_key($href, $text) {
    $needsKey = ($this->platform != 'bbplus' && self::$access_key < 10);
    if ($needsKey) {
      $innerText = self::$access_key . ': ' . $text;
    } else {
      $innerText = $text;
    }

    if ($href) {
      $result = '<a href="' . $href . '"'
              . ($needsKey ? ' accesskey="' . self::$access_key . '"' : '')
              . '>' . $innerText . '</a>';
    } else {
      $result = $innerText;
    }

    self::$access_key++;
    return $result;
  }

  public function reset_access_key($index=0) {
    self::$access_key = $index;
  }

  public function meta_tags() {
    $result = '';
    foreach (array('viewport' => $this->viewport_content,
                   'MobileOptimized' => $this->meta_mobile_optimized,
		   'HandheldFriendly' => 'True') as $name => $content)
    {
      if ($content) {
        $result .= '<meta name="' . $name . '" content="' . $content . '">' . "\n";
      }
    }
    return $result;
  }

}

?>
