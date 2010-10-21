<?

class TouchPage extends Page {

  protected $navbar_image;
  protected $home;
  protected $breadcrumb;
  protected $viewport_device_width;
  /*
  protected $homegrid_css;

  public $centered_image_width;
  public $centered_image_height;
  public $centered_image_font_size;
  */

  public function __construct($platform, $certs) {
    $this->branch = 'Touch';
    $this->platform = $platform;
    $this->certs = $certs;
    $this->max_list_items = 25;

    // inline_css may not be used anymore
    $this->varnames= Array(
     'header', 'title', 'viewport_device_width', 
     'stylesheets', 'help_on', // 'inline_css', 'homegrid_css', 
     'home', 'navbar_image', 'breadcrumb', 'footer', 'standard_footer',
     //'centered_image_width', 'centered_image_height', 'centered_image_font_size',
    );

    $attribs_file = WEBROOT . "$this->branch/attribs-$this->platform.php";
    if (!file_exists($attribs_file)) {
      $attribs_file = WEBROOT . "$this->branch/attribs-generic.php";
    }
    include($attribs_file);
    $this->viewport_device_width = $viewport_device_width;
    //$this->centered_image_width = $centered_image_width;
    //$this->centered_image_height = $centered_image_height;
    //$this->centered_image_font_size = $centered_image_font_size;

    //$this->inline_css = $extra_css;
    //$this->home_css = $home_css;

  }

  public function navbar_image($navbar_image) {
    $this->navbar_image = $navbar_image;
    return $this;
  }

  public function breadcrumb_home() {
    $this->home = True;
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