<?php


if ($page->branch == 'Webkit') {
  $location_img = $page->img_tag('location', 'gif', 'Map', array('width' => 280, 'height' => 238));
} else {
  $location_img = $page->img_tag('location', 'gif', 'Map');
}

$location =<<<HTML
<p>Waiting for copy</p>
HTML;

$google =<<<HTML
<p>Waiting for copy</p>
HTML;

$google_extra =<<<EXTRA
<li>Waiting for copy</li>
EXTRA;

$logan =<<<HTML
<p>Waiting for copy</p>
HTML;

$logon_extra =<<<EXTRA
 <li>Waiting for copy</li>
EXTRA;

$t =<<<HTML
<p>Waiting for copy</p>
HTML;

$car =<<<HTML
<p>Waiting for copy</p>
HTML;

$parking =<<<HTML
<p>Waiting for copy</p>
HTML;

$directions = array(
  "location" => dirs($location, "Where in the World is " . INSTITUTION_NAME . "?", "Whereis")
      ->heading("Where is " . INSTITUTION_NAME . "?"),
  "google" => dirs($google, "Finding " . INSTITUTION_NAME . " using Google Maps", "Google")
      ->google($google_extra),
  "logan" => dirs($logan, "From Logan Airport", "Logan")
      ->google($logan_extra),
  "t" => dirs($t, 'By Public Transportation - MBTA ("The T")', "By T")
      ->short_link('By Public Transport ("The T")', $page),
  "car" => dirs($car, "By Car", "By Car"),
  "parking" => dirs($parking, "Parking Suggestions", "Parking")
);


class DirectionPage {
  public $header;
  public $html;
  public $breadcrumb;
  public $google_html;
  public $link_text;

  public function __construct($html, $link_text, $breadcrumb) {
    $this->html = $html;
    $this->link_text = $link_text;
    $this->breadcrumb = $breadcrumb;
    $this->header = $this->link_text;
  }

  public function heading($header) {
    $this->header = $header;
    return $this;
  }

  public function google($google_html) {
    $this->google_html = $google_html;
    return $this;
  }

  public function short_link($link_text, $page) {
    if($page->branch != "Webkit") {
      $this->link_text = $link_text;
    }
    return $this;
  }
}

function dirs($html, $link_text, $breadcrumb) {
  return new DirectionPage($html, $link_text, $breadcrumb);
}

function directionsURL($link) {
  return "directions.php?page=$link";
}

if(isset($_REQUEST['page'], $directions[$_REQUEST['page']])) {
  $info = $directions[ $_REQUEST['page'] ];
  require "$page->branch/direction.html";
} else {
  require "$page->branch/directions.html";
}

$page->output();

?>
