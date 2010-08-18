<?php

class TouchPage extends Page {

  protected $navbar_image;
  protected $home;
  protected $breadcrumb;
  protected $module_home_link = "./";
  protected $viewport_device_width;

  public function __construct($platform, $certs) {
    $this->branch = 'Touch';
    $this->platform = $platform;
    $this->certs = $certs;
    $this->max_list_items = 25;

    $this->varnames= Array(
     'header', 'title', 'viewport_device_width', 
     'stylesheets', 'help_on',
     'home', 'navbar_image', 'breadcrumb', 'module_home_link',
     'footer', 'standard_footer',
    );

    $attribs_file = WEBROOT . "/$this->branch/attribs-$this->platform.inc";
    $this->viewport_device_width = isset($viewport_device_width) ? $viewport_device_width : '0';
  }

  public function module($module) {
    $this->title = Modules::$module_data[$module]['title'];
    $this->header = Modules::$module_data[$module]['title'];
    $this->navbar_image = $module;
    $this->module = $module;
    return $this;
  }

  public function header($header) {
    $this->header = $header;
    return $this;
  }

  public function navbar_image($navbar_image) {
    $this->navbar_image = $navbar_image;
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

  public function viewport() {
    $meta_str = '<meta name="viewport" content="';
    if ($this->viewport_device_width) {
      $meta_str .= 'width=device-width, ';
    }
    $meta_str .= 'initial-scale=1.0, user-scalable=false" />';
    return $meta_str;
  }

  public function get_homegrid_css() {
    return $this->homegrid_css;
  }

}

?>
