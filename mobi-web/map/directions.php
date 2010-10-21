<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require_once WEBROOT . "page_builder/page_header.php";

if ($page->branch == 'Webkit') {
  $location_img = $page->img_tag('location', 'gif', 'Map', array('width' => 280, 'height' => 238));
} else {
  $location_img = $page->img_tag('location', 'gif', 'Map');
}

$location =<<<HTML
<p>$location_img</p>
<p><a href="http://maps.google.com/maps?f=q&q=77+Massachusetts+Avenue,+Cambridge+MA+02139">MIT is located</a> on the north 
shore of the Charles River Basin in Cambridge, Massachusetts, USA. The campus is within 
3 miles of two major interstate highways, less than 6 miles from a major international airport, and is accessible via public transportation. MIT is a 15-30 minute walk from downtown Boston (depending on the weather). MIT is a 30-40 minute walk from Harvard University (located just up the river from the MIT campus).</p>
HTML;

$google =<<<HTML
<p>To find MIT using Google Maps, use the address "<a href="http://maps.google.com/maps?f=q&q=77+Massachusetts+Avenue,+Cambridge,+MA+02142">77 Massachusetts Avenue, Cambridge MA 
02139</a>" as your reference point for general directions to MIT. This is the famous <a 
href="detail.php?selectvalues=7">domed building</a> at the center of campus.</p>
HTML;

$google_extra =<<<EXTRA
<li><a href="http://maps.google.com/maps?f=q&q=77+Massachusetts+Avenue,+Cambridge+MA+02142" class="external">MIT on Google Maps</a></li>
EXTRA;

$logan =<<<HTML
<p><strong>By taxi:</strong> Taxi fare from the airport is about $20-$25. During non-rush hour, the taxi ride will take about 15 minutes. During rush hour, the ride could take 30 minutes or more.</p>
<p><strong>By subway:</strong> From any terminal at Logan Airport, take the Silver Line bus to South Station. At South Station, change to the Red Line subway to Kendall/MIT (inbound toward Alewife). Under normal conditions the ride will take about one-half hour and the fare is $1.70 (Charlie Card) or $2.00 (cash).</p>

<p><strong>By car:</strong> Leaving the airport, follow the signs to the Sumner Tunnel. Enter the tunnel and stay in the right lane.<br/>At the end of the tunnel, continue to stay in the right lane, start down an incline and bear to the right immediately at the sign for Storrow Drive.<br/>Take Exit 26 for Cambridge/ Somerville. Follow the signs for Back Bay/Cambridge (do not take the exit for Cambridge/ Somerville).<br/>Stay in the right lane and follow the signs for Storrow Drive Westbound.<br/>After you pass under the pedestrian walkbridges, change to the left lane and take the exit on your left for 2A North.<br/>Turn right onto the Harvard Bridge (Massachusetts Avenue).<br/>MIT's main entrance is 77 Massachusetts Avenue, and it will be on the right at the second set of traffic lights.</p>
HTML;

$logon_extra =<<<EXTRA
 <li><a href="http://maps.google.com/maps?f=d&hl=en&geocode=&saddr=1+Harborside+Dr,+Boston+MA+02128&daddr=77+Massachusetts+Ave,+Cambridge,+MA+02139&sll=42.366973,-71.027629&sspn=0.010004,0.017381&ie=UTF8&t=h&z=14" class="external">Directions via Google Maps</a></li>
EXTRA;

$t =<<<HTML
<p><strong>Subway:</strong> Take the Red Line subway to the Kendall/MIT Station or to the Central Square Station, both of which are a short walk from campus. The walk from Central Square takes about 10 minutes and takes you right down Massachusetts Avenue. The Kendall/MIT Station is on the eastern side of campus, and as soon as you enter an MIT building you can get to the other buildings without going outside.</p>
<p><strong>Bus:</strong> The #1 or Dudley/Harvard Station bus stops at MIT on Massachusetts Avenue and provides transportation to Central Square and Harvard Square (Northbound), and Boston (Southbound). The MIT stop is at a large crosswalk with a stoplight. On one side of the street are steps leading up to large Ionic columns and the small dome of MIT; on the other side of the street is the Stratton Student Center and Kresge Oval (an open, grass-covered area). Additionally, the CT1 (Crosstown bus) stops at the MIT stop on Massachusetts Avenue and the CT2 bus stops on the corner of Massachusetts Avenue and Vassar St. as well as Kendall Square T Station.</p>
HTML;

$car =<<<HTML
<p><strong>From the North (I-95 or I-93):</strong> If you are heading south on I-93, follow I-93 into Boston then follow the I-93 instructions below. If you are heading south on I-95, take the I-93 South exit (exit 37) then follow the instructions from I-93. Alternatively, take the I-90 East exit (Massachusetts Turnpike) from I-95 then follow the instructions from the West via I-90.</p>
<p><strong>From the South (I-95 or I-93):</strong> If you are heading north on I-93, follow I-93 into Boston then follow the I-93 instructions below. If you are heading north on I-95, take the I-93 North exit then follow the instructions from I-93. Alternatively, take the I-90 East exit from I-95 then follow the instructions from I-90.</p>

<p><strong>From the West (I-90) (Mass Turnpike):</strong> Follow I-90 east to the Cambridge/Brighton exit (exit 18). Following the signs to Cambridge, cross the River Street Bridge, and continue straight about 1 mile to Central Square. Turn right onto Massachusetts Avenue and follow Massachusetts Avenue for about a half mile. The main entrance to MIT will be on your left. If you cross the river again, you have gone too far.</p>
<p><strong>From Route I-93:</strong> From I-93, take exit 26, and follow the signs to Back Bay along Storrow Drive West, approximately 1.5 miles, to the exit for Route 2A. The exit will be on the left, just before the Harvard Bridge (more appropriately called the Massachusetts Avenue Bridge). The Charles River will be on your right. As you cross the bridge, you will be looking at MIT - the Great Dome and academic facilities are on the right, the dormitories and athletic facilities are on the left.</p>
HTML;

$hood =<<<HTML
<p>Take the blimp to the tall building with all the glass windows (that would be the Hancock tower). Head north over the Charles River and have them put you down on top of the large, convex, concrete structure on the north shore of the river (that would be the Great Dome of MIT). Watch out for police cars on the roof.</p>
HTML;

$parking =<<<HTML
<p>Parking in Cambridge and Boston is generally not an enjoyable experience. Whenever possible, park your car at the hotel 
at which you are staying, and use <a href="directions.php?page=t">public transportation</a> to get to the MIT campus. 
If you must drive to the campus, there is both on- and off-street parking available, but most public 
parking is not very close to the center of the MIT campus (unless you arrive early in the morning or late in the evening).</p>
<p>There is metered parking on Massachusetts Avenue for short stays and evenings/weekends, as well as a number of lots at 
which you may park for a fee. These include <a href="detail.php?selectvalues=P16">Vassar St. Public Parking</a> at the 
corner of 
Massachusetts Avenue and Vassar Street, <a href="detail.php?selectvalues=P37">University Park / Star Market Public 
Parking</a>, and 
the <a href="detail.php?selectvalues=P10">Marriott Parking Garage</a> on Ames St. and Broadway.</p>

<p>If you were invited to campus and a visitor parking hang tag was given to you, you may park in the lots specified on the 
hang tag. Please check the <a href="./?category=parking">parking lot</a> listing and plan your trip 
accordingly.</p>
HTML;

$directions = array(
  "location" => dirs($location, "Where in the World is MIT?", "Whereis")
      ->heading("Where is MIT?"),
  "google" => dirs($google, "Finding MIT using Google Maps", "Google")
      ->google($google_extra),
  "logan" => dirs($logan, "From Logan Airport", "Logan")
      ->google($logan_extra),
  "t" => dirs($t, 'By Public Transportation - MBTA ("The T")', "By T")
      ->short_link('By Public Transport ("The T")', $page),
  "car" => dirs($car, "By Car", "By Car"),
  "hood" => dirs($hood, "By Hood Blimp", "Blimp"),
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

if($_REQUEST['page']) {
  $info = $directions[ $_REQUEST['page'] ];
  require "$page->branch/direction.html";
} else {
  require "$page->branch/directions.html";
}

$page->output();

?>
