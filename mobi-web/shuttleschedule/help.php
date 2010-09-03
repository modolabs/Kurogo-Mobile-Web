<?php

$header = "Shuttle Schedule";
$module = "shuttleschedule";

$help = array(
  'Find the expected time when an MIT shuttle bus should arrive at each stop.  If a shuttle is currently running, the next scheduled stop is indicated by a red highlight and red shuttle-bus icon.',

  'These times are based on NextBus GPS tracking when it is available.  When GPS tracking is not available, the times default to the published schedule.',

  'Tech Shuttle, Northwest Shuttle, and Boston Daytime shuttles run during the day on weekdays.  Boston East, Boston West, Cambridge East, and Cambridge West shuttles run every night during the school year.  Boston All and Cambridge All shuttles run every night during the summer and holidays.',

  'All route detail pages except the summer Saferide routes include a full route map highlighting the estimated next stop. To see this map, scroll down (for feature phones and smartphones) or use the Route Map tab (iPhone and iPod Touch). On the iPhone and iPod Touch, rotating your device will display the schedule and route map side-by-side.',

  'To update the estimated times and route map, click the &lsquo;Refresh&rsquo; link near the top of the page or use your web browser&apos;s &lsquo;Refresh&rsquo; command.',
);
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . "page_builder/help.php";

?>
