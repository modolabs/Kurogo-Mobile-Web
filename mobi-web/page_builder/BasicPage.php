<?

class BasicPage extends Page {

  protected $header; // html header
  protected $extra_links = array();
  protected $help_links = array();
  protected $bottom_nav_links = array();
  public $fontsize;

  public function __construct($platform, $certs) {
    $this->branch = 'Basic';
    $this->platform = $platform;
    $this->certs = $certs;
    $this->max_list_items = 20;

    // get font size from cookie
    if (isset($_COOKIE['layout'])) { // this should already be set by Page
      $params = split(',', $_COOKIE['layout']);
      $this->fontsize = $params[3];
    }
    $cookie_params = "$this->branch,$this->platform,$this->certs";

    // get font size from selection (override cookie value)
    if (isset($_REQUEST['font'])) {
      $fontsize = $_REQUEST['font'];
      $cookie_params .= ",$fontsize";
      setcookie('layout', $cookie_params, time() + LAYOUT_COOKIE_LIFESPAN, '/');
      $this->fontsize = $fontsize;
    }

    switch ($this->fontsize) {
    case 'small':
      $this->inline_css = 'body { font-size: 80% }';
      break;
    case 'large':
      $this->inline_css = "body { font-size: 120% } .label { padding-top:0; } .inputcombo .combobutton { padding: .1em .2em; }";
      break;
    case 'xlarge':
      $this->inline_css = 'body { font-size: 150% }';
      break;
    default:
      $this->inline_css = 'body { font-size: 100% }';
      break;
    }

    $this->varnames = Array(
      'header', 'title', 'stylesheets', 'help_on',
      'extra_links', 'help_links', 'bottom_nav_links', 'inline_css',
      'centered_image_width', 'centered_image_height', 'centered_image_font_size',
    );
  }

  public function module($module) {
    $this->title = Modules::$module_data[$module]['title'];
    $this->header = Modules::$module_data[$module]['title'];
    $this->module = $module;
    return $this;
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
	$font_html[] = '<span class="font' .$fontsize . '">A</a>';
      } else {
	$font_html[] = '<a href="index.php?font=' . $fontsize . '" class="font' . $fontsize . '">A</a>';
      }
    }
    return implode(' | ', $font_html);
  }

}

?>
