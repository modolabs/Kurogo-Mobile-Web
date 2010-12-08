<?
require_once("api_header.inc");

$module = $_REQUEST['module'];
PageViews::log_api($module, 'iphone');

switch ($module) {
 case 'emergency':
   require('emergency.php');
   break;

 case 'people':
   require('people.php');
   break;

 case 'courses':
    require('coursesApi.php');
    break;

 case 'calendar':
   require('HarvardCalendar.php');
   break;

 case 'map':
   require('map.php');
   break;

 case 'news':
   require('news.php');
   break;

 //case 'push':
 //  require('apns_push.php');
 //  break;

 case 'dining':
   require('dining.php');
   break;

 case 'shuttles':
   require('shuttles.php');
   break;

case 'libraries':
    require('libraries.php');
    break;

 default:
   $data = Array('error' => 'not a valid query');
   echo json_encode($data);
   break;
}



?>
