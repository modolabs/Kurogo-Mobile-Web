<?php

class WebkitPage extends Page {

  protected $navbar_image;
  protected $home = False; // used to be orphaned var "breadcrumb_root"
  protected $breadcrumbs = array();
  protected $last_breadcrumb;
  protected $breadcrumb_links;
  protected $breadcrumb_classes;
  protected $module_home_link = "./";
  protected $extra_onload = "scrollTo(0,1);";
  protected $onorientationchange;
  protected $raw_js = array();
  protected $scalable = "yes";
  protected $fixed = False;

  public function __construct($platform, $certs) {
    $this->branch = 'Webkit';
    $this->platform = $platform;
    $this->certs = $certs;

    $this->varnames = Array(
      'header', 'title', 'stylesheets', 'help_on',
      'navbar_image', 'inline_css', 'javascripts',
      'breadcrumb_links', 'breadcrumb_classes', 'home', 'breadcrumbs', 
      'last_breadcrumb', 'module_home_link',
      'footer', 'footer_script', 'standard_footer',
      'extra_onload', 'onorientationchange', 'raw_js', 'scalable', 'fixed',
    );

    include WEBROOT . "/$this->branch/attribs-$this->platform.inc";
    $this->inline_css = isset($extra_css) ? $extra_css : '';
    $this->home_css = $home_css;

  }

  public function fixed() {
    $this->fixed = True;
    return $this;
  }

  public function not_scalable() {
    $this->scalable = "no";
    return $this;
  }

  public function module($module) {
    $this->navbar_image = $module;
    $this->title = isset(Modules::$module_data[$module]) ?
        Modules::$module_data[$module]['title'] : '';
    $this->module = $module;
    return $this;
  }

  public function navbar_image($navbar_image) {
    $this->navbar_image = $navbar_image;
    return $this;
  }

  public function breadcrumbs() {
    $this->breadcrumbs = func_get_args();
    $this->last_breadcrumb = array_pop($this->breadcrumbs);
    $this->breadcrumb_links = array();
    return $this;
  }

  public function breadcrumb_links() {
    $tmp = func_get_args();
    for($cnt = 0; $cnt < count($tmp); $cnt++) {
      $this->breadcrumb_links[$cnt] = $tmp[$cnt];
    }
    return $this;
  }

  public function breadcrumb_classes() {
    $tmp = func_get_args();
    for($cnt = 0; $cnt < count($tmp); $cnt++) {
      $this->breadcrumb_classes[$cnt] = $tmp[$cnt];
    }
    return $this;
  }

  public function breadcrumb_home() {
    $this->home = True;
    return $this;
  }

  public function module_home_link($link) {
      $this->module_home_link = $link;
      return $this;
  }

  /*
   * An alternative method for defining the breadcrumbs
   * from a navigation array
   */
  public function generate_navigation($navigation_array) {
      $navigation_depth = sizeof($navigation_array);
      $navigation_links = array_values($navigation_array);

      if($navigation_depth <= 1) {
          $this->breadcrumb_home();
      }

      if($navigation_depth >= 1) {
          $this->module_home_link = $navigation_links[0];
      }

      $this->breadcrumb_links = array();
      if($navigation_array > 1) {
          $this->breadcrumb_links = array_slice($navigation_links, 1, -1);
      }

      $navigation_labels = array_keys($navigation_array);
      if(sizeof($navigation_labels) > 1) {
          $navigation_labels = array_slice($navigation_labels, 1);
      }
      call_user_func_array(array($this, 'breadcrumbs'), $navigation_labels);

      return $this;
  }

  public function extra_onload($js) {
    $this->extra_onload .= " $js";
    return $this;
  }

  public function body_onload($js) {
    $this->extra_onload = $js;
    return $this;
  }

  public function onorientationchange($js) {
    $this->onorientationchange = $js;
    return $this;
  }

  public function add_inline_script($js) {
    $this->raw_js[] = $js;
    return $this;
  }

  public function inline_js_begin() {
    $this->acquire_begin("inline_js");
  }

  public function inline_js_end() {
    $this->acquire_end("inline_js");
    $this->raw_js[] = $this->inline_js;
  }
}

?>
