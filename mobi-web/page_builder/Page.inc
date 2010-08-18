<?php

require_once WEBROOT . '/home/Modules.inc';

require_once 'WebkitPage.inc';
require_once 'TouchPage.inc';
require_once 'BasicPage.inc';

require_once 'counter.inc';

class Page {
  // sections of the HTML page
  protected $module;
  protected $title; // displayed on browser title bar
  protected $cache_control = 'max-age=86400';
  protected $stylesheets = array();
  protected $javascripts = array();
  protected $container;
  protected $header;
  protected $footer="";
  protected $footer_script = NULL;
  protected $standard_footer = True;
  protected $compressed_mode = True;
  protected $inline_css;

  // keep track of sections of the HTML page that have been created
  protected $acquire_mode = array();
  protected $acquired = array();

  // stuff that differentiates each device type
  public $branch;
  public $platform;
  public $certs = FALSE;

  private static function getDeviceClass() {
    global $ini;

    // get properties and specific device type from mobi-service
    $request = Array(
      'user-agent' => $_SERVER['HTTP_USER_AGENT'],
      );

    $url = $ini['MOBI_SERVICE_URL'] . '?' . http_build_query($request);
    $response = file_get_contents(
      $ini['MOBI_SERVICE_URL'] . '?' . http_build_query($request));

    $params = json_decode($response, TRUE);

    return array(
      $params['pagetype'],
      $params['platform'],
      $params['supports_certificate']
      );
  }

  public static function factory() {

    if (isset($_COOKIE['layout'])) {
      $params = explode(',', $_COOKIE['layout']);

      // If we set an explicit branch= as a querystring param, override the 
      // cookie value.  For debugging.
      $branch = isset($_GET['branch']) ? $_GET['branch'] : $params[0];
      $platform = isset($_GET['platform']) ? $_GET['platform'] : $params[1];

      // A little paranoia, since we branch to build paths...
      if ($branch != "Webkit" && $branch != "Touch" && $branch != "Basic") {
          $branch = "Basic";
      }

      $certs = $params[2];
    }

    if (!isset($params) || sizeof($params) != 3 || !$params[0]) {
      global $ini;

      // get properties and specific device type from mobi-service
      $request = Array(
        'user-agent' => $_SERVER['HTTP_USER_AGENT'],
        );

      $url = $ini['MOBI_SERVICE_URL'] . '?' . http_build_query($request);
      $response = file_get_contents(
        $ini['MOBI_SERVICE_URL'] . '?' . http_build_query($request));

      $params = json_decode($response, TRUE);

      $branch = $params['pagetype'];
      $platform = $params['platform'];
      $certs = $params['supports_certificate'];

      setcookie('layout', "$branch,$platform,$certs", time() + $ini['LAYOUT_COOKIE_LIFESPAN'], '/');
    }

    $page = $branch . "Page";

    return new $page($platform, $certs);
  }

  public function prevent_caching($pagetype) {
    $this->cache_control = 'no-cache';
    return $this;
  }

  public function cache() {
    header("Cache-Control: $this->cache_control");
    return $this;
  }

  public function module($module) {
    $this->title = Modules::$module_data[$module]['title'];
    $this->module = $module;
    return $this;
  }

  public function title($title) {
    $this->title = $title;
    return $this;
  }

  public function header($header) {
    $this->header = $header;
    return $this;
  }

  public function add_stylesheet($stylesheet) {
    if(gettype($stylesheet) == 'string') {
      $this->stylesheets[] = array('href' => "$stylesheet.css");
    } else {
      // $stylesheet needs to an array that defines attributes
      $this->stylesheets[] = $stylesheet;
    }
    return $this;
  }

  public function add_javascript($js_name) {
    $this->javascripts[] = $js_name;
    return $this;
  }

  public function add_inline_css($css) {
    $this->inline_css .= $css . "\n";
  }

  public function footer_script($script) {
    $this->footer_script = $script;
    return $this;
  }

  public function extra_footer($footer) {
    $this->footer = $footer . ' ';
    return $this;
  }

  public function custom_footer($footer) {
    $this->standard_footer = False;
    $this->footer = $footer;
    return $this;
  }

  protected $help_on = True;

  public function help_off() {
    $this->help_on = False;
    return $this;
  }

  public function acquire_begin($name) {
    if(in_array($name, $this->acquire_mode)) {
      throw new Exception("$name already begun");
    } elseif (in_array($name, $this->acquired)) {
      throw new Exception("$name already set");
    } else {
      ob_start();
      $this->acquire_mode[] = $name;
    }
  }

  public function acquire_end($name) {
    if(in_array($name, $this->acquired)) {
      throw new Exception("$name already acquired");
    } elseif(in_array($name, $this->acquire_mode)) {
      $this->$name = ob_get_clean();
      $this->acquired[] = $name;
    } else {
      throw new Exception("$name never begun");
    }
  }

  public function content_begin() {
    $this->acquire_begin("content");
  }

  public function content_end() {
    $this->acquire_end("content");
  }

  public function inline_css_begin() {
    $this->acquire_begin("inline_css");
  }

  public function inline_css_end() {
    $this->acquire_end("inline_css");
  }

  public function output() {

    if (!$this->module) {
      // here is a stricter version of the overly permissive regex
      // that used to be in page_header
      $module_list = implode('|', array_keys(Modules::$module_data));
      if (preg_match('/\/(' . $module_list . ')\/[^\/]*?$/', $_SERVER['REQUEST_URI'], $match)) {
        $this->module = $match[1];
      }
    }

    PageViews::increment($this->module, $this->platform);

    foreach($this->varnames as $varname) {
      ${$varname} = $this->$varname;
    }
    
    ob_start();
      require "../$this->branch/base.html";
    $uncompressed_html = ob_get_clean();

    // replace large chunks of spaces with a single space
    $compressed_html = self::compress_whitespace($uncompressed_html);
    if($this->compressed_mode) {
      echo $compressed_html;
    } else {
      echo $uncompressed_html;
    }
  }

  public static function compress_whitespace($html) {
    $compressed_html = preg_replace('/\s*?\n\s*/', "\n", $html);
    $compressed_html = preg_replace('/( |\t)( |\t)*/', " ", $compressed_html);
    return $compressed_html;
  }

  protected function draw_content() {
    //draw the main content of the page
    echo $this->content;
  }

  public function is_computer() {
    return ($this->platform == 'computer');
  }

  public function is_spider() {
    return ($this->platform == 'spider');
  }

  public function get_inline_css() {
    return $this->inline_css;
  }

  // call output() after calling this
  public function prepare_error_page($title, $module, $error_text) {
    $this->title = $title . ': Error';
    $this->header($title);
    $this->content = '<div class="focal">' . $error_text . '</div>';
    if ($this->branch == 'Webkit') {
      $this->breadcrumbs($title);
    }
    if ($this->branch == 'Webkit' || $this->branch == 'Touch') {
      $this->navbar_image = $module;
      $this->breadcrumb_home();
    }
  }

  /*
   * generate img tags that automatically supply width and height
   * search current directory for PAGETYPE/images/IMAGENAME.EXTENSION
   * if not found, search images/ in the parent directory
   */
  public function img_tag($name, $extension, $alt_text='', $attribs=NULL) {

    $delta_file = $this->branch . '/images/' . $name . '.' . $extension;
    if (!file_exists($delta_file)) 
      $delta_file = '../' . $this->branch . '/images/' . $name . '.' . $extension;

    if (file_exists($delta_file)) {
      $img_attribs = Array(
         'src' => $delta_file,
	 'alt' => $alt_text,
	 );

      $img_specs = getimagesize($delta_file);
      $img_attribs['width'] = $img_specs[0];
      $img_attribs['height'] = $img_specs[1];
      if (is_array($attribs)) {
	foreach ($attribs as $key => $value) {
	  $img_attribs[$key] = $value;
	}
      }

      $img_str = '<img ';
      foreach ($img_attribs as $key => $value) {
	$img_str .= $key . '="' . $value . '" ';
      }
      $img_str .= '/>';
      return $img_str;
    }
  }

  /* generate the html for the extra style sheet links */
  public function stylesheet_links() {
    $output = "";
    foreach($this->stylesheets as $stylesheet) {
      $output .= '<link rel="stylesheet" type="text/css"';
      foreach($stylesheet as $attribute => $value) {
        $output .= " $attribute=\"$value\"";
      }
      $output .= " />\n";
    }
    return $output;
  }

  // for platforms under this branch that need differential treatment,
  // search for a file that looks like "filename-platform.ext"
  // otherwise use "filename.ext" as default
  public function delta_file($name, $extension, $path='') {
    $delta_file = $path . $this->branch . '/' . $name . '-' . $this->platform . '.' . $extension;
    if (file_exists($delta_file)) {
      return $delta_file;
    } else {
      return $path . $this->branch . '/' . $name . '.' . $extension;
    }
  }
}

?>
