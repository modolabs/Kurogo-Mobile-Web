<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once $docRoot . "/mobi-config/mobi_web_constants.php";
require WEBROOT . 'mobile-about/WhatsNew.php';
$whats_new = new WhatsNew();
$new_items = $whats_new->get_items();
WhatsNew::setLastTime();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="content-script-type" content="text/javascript" />
	<title>MIT Mobile Web</title>
	
	<link rel="stylesheet" rev="stylesheet" href="helpsite.css" type="text/css" />
	<script type="text/javascript">
	<!--
		function preview() {
		// Launches a new, small browser window containing the MIT Mobile Web in preview mode for desktop and laptop computers
			var popup = window.open('preview.html','popwin',
'left=20,top=20,innerwidth=260,innerheight=320,toolbar=0,menubar=0,location=0,status=1,resizable=1,scrollbars=yes');
			// window.location.href="preview.html";
		}	
	//-->
	</script>
</head>
<body>
  <div id="container">
	<div id="focal">
		<div class="content">
			<div id="cluster"><img src="images/home/cluster.gif" alt="MIT Mobile Web" width="900" height="488" /></div>
			<div id="istlogo"><a href="http://web.mit.edu/ist/"><img src="images/home/ist.gif" alt="IS&amp;T" width="101" height="39" /></a></div>
			<div id="introduction">
				<h1><a href="http://web.mit.edu" style="float:left"><img src="images/home/title-logo.gif" alt="MIT" width="73" height="111" /></a><img src="images/home/title.gif" alt="Mobile Web" width="315" height="111" /></h1>
				
				<p class="lead">Get essential MIT information and services anytime, anywhere on your mobile device: <strong><a href="http://m.mit.edu/">m.mit.edu</a></strong></p>
				<p>The MIT Mobile Web offers up-to-date information, optimized for different types of mobile devices. Find people, places, events, course news, shuttle schedules, and more. All you need is a mobile device with a web browser and either WiFi or a data plan<strong><a href="#disclaimer">*</a></strong></p>
				
				<a id="preview" href="preview.html" onclick="preview(); return false;"><img src="images/home/icon-preview.gif" width="40" height="36" alt="Preview" class="previewicon" />Click here to preview the site on your desktop or laptop.</a>
			</div>
		</div>
	</div>
	<div id="support">
		<div class="content">
			<div id="details">
				<h2>The MIT Mobile Web includes:</h2>

				<div class="module">
					<img class="thumbnail" src="images/modules/people-thumb.gif" width="160" height="160" alt="People" />
					<h3>People Directory</h3>
					<p>Find students, faculty and staff at MIT by searching part or all of their name, email address, or phone number. Get one-click access to call or email them, or to locate their office (where available).</p>
				</div>

				<div class="module">
					<img class="thumbnail" src="images/modules/map-thumb.gif" width="160" height="160" alt="Map" />
					<h3>Campus Map</h3>
					<p>Find buildings, parking, landmarks and more in this interactive live map. Search by building number or name, or browse by different categories. You can also search by keyword &ndash; such as ‘media lab’, ‘la verdes’, ‘tennis’, and so on. <strong>iPhone tip:</strong> Switch to full-screen mode using the last button under the map image. You can also rotate the full-screen map.</p>
				</div>

				<div class="module">
					<img class="thumbnail" src="images/modules/shuttle-thumb.gif" width="160" height="160" alt="Shuttle" />
					<h3>Shuttle Schedule</h3>
					<p>Can you catch the bus in time? Find out &ndash; wherever you are &ndash; with up-to-the-minute schedules and route maps for each of the MIT daytime and nighttime (Saferide) shuttles. <strong>iPhone tip:</strong> When you're viewing a shuttle route, rotate your iPhone/iPod Touch to the horizontal (landscape) orientation to see the schedule and route map side-by-side.</p>
				</div>

				<div class="module">
					<img class="thumbnail" src="images/modules/events-thumb.gif" width="160" height="160" alt="Calendar" />
					<h3>Events Calendar</h3>
					<p>Find out what's going on around campus. Search by keyword and time period, or browse by category. Once you've found your event, get one-click access to the campus map to find out how to get there.</p>
				</div>

				<div class="module">
					<img class="thumbnail" src="images/modules/stellar-thumb.gif" width="160" height="160" alt="Stellar" />
					<h3>Stellar</h3>
					<p>Get the latest news and announcements for any class with a Stellar site. You can also find faculty and staff contact info (and instantly look them up in the people directory).</p>
				</div>

                                <div class="module">
                                        <img class="thumbnail" src="images/modules/techcash-thumb.gif" width="160" height="160" alt="Tech Cash" />
                                        <h3>Tech Cash (BETA)</h3>
                                        <p>Get up-to-the-minute account balances
 and reports of recent activity on your Tech Cash accounts (currently for iPhone
 only).</p>
                                </div>

<!--
				<div class="module">
					<img class="thumbnail" src="images/modules/careers-thumb.gif" width="160" height="160" alt="Career Services" />
					<h3>Student Career Services</h3>
					<p>Find out what companies are coming to campus to present informational sessions or recruiting seminars &ndash; then click to find the meeting locations in the Campus Map. <strong>Coming soon!</strong></p>
				</div>
-->
				<div class="module">
					<img class="thumbnail" src="images/modules/emergency-thumb.gif" width="160" height="160" alt="Emergency Info" />
					<h3>Emergency Information</h3>
					<p>Learn about any emergencies on campus, and get one-click access to campus police, medical services and other emergency phone numbers.</p>
				</div>

				<div class="module">
					<img class="thumbnail" src="images/modules/3down-thumb.gif" width="160" height="160" alt="3DOWN" />
					<h3>3DOWN</h3>
					<p>Get the latest status updates on many of MIT's essential tech services, including phone, email, web, network services, and more.</p>
				</div>
				
				<div class="module">
					<p>And this is just the beginning! More modules are already in the works. Please check back often at <strong><a href="http://m.mit.edu/">m.mit.edu</a></strong> to see what's new and updated.</p>
				</div>

				<div id="footer">
					<div id="footerlogo"><a href="http://web.mit.edu/"><img src="http://web.mit.edu/graphicidentity/interface/mit-greywhite-footer3.gif"
alt="MIT" width="62" height="36" ></a></div>
					<p>Copyright &copy;2009-2010 <a href="http://web.mit.edu">Massachusetts Institute of Technology</a>. All rights reserved. The MIT Mobile Web is designed, developed and maintained by <a href="http://web.mit.edu/ist/">Information Systems &amp; Technology (IS&amp;T)</a>. For more information, or for help using the MIT Mobile Web, please contact us at <a href="mailto:&#109;&#111;&#98;&#105;&#119;&#101;&#98;&#64;&#109;&#105;&#116;&#46;e&#100;&#117;">&#109;o&#98;&#105;&#119;e&#98;&#64;&#109;i&#116;&#46;&#101;d&#117;</a>.</p>
					<p><a name="disclaimer"> </a>
					* Terms and Conditions: The MIT Mobile Web is a free service. <strong>Extra data charges may apply</strong> when using any website on your mobile device depending on your service plan.
					</p>
				</div>
			</div>
			
			<div id="sidebar">
				<div id="iphoneapp">
					<a href="iphoneapp.html"><img src="images/home/iphoneapp-icon.gif" alt="iPhone App" width="57" height="57" class="floatleft" /></a><h2><a href="iphoneapp.html">MIT Mobile iPhone App</a></h2>
					The MIT app for your iPhone or iPod Touch offers MIT news, real-time GPS shuttle tracking, push notifications for upcoming shuttles and class announcements, and  more. Now available in the App Store.<br/><a href="iphoneapp.html">Learn more &gt;</a>
				</div>
				<div id="androidapp">
					<a href="androidapp.html"><img src="images/androidapp/qrcode.png" alt="Android App" width="90" height="90" class="floatleft" /></a><h2><a href="androidapp.html">MIT Mobile Android App</a></h2>
					MIT Mobile for Android offers MIT news, real-time GPS shuttle tracking, notifications for upcoming shuttles and class announcements, and  more. Now available on the Android Market.<br/><a href="androidapp.html">Learn more &gt;</a>
				</div>
				<div id="whatsnew">
					<h2>What's New</h2>
					<ul>
					<? foreach ($new_items as $index => $content) { ?>
					<? if ($index >= 3) { break; } ?>
					<li><strong><?=$content['title'] ?>:</strong> <?=$content['body'] ?> (<?=$content['date']['month']?>/<?=$content['date']['day']?>)</li>
					<? } ?>
					</ul>
				</div>
				
				<div id="howto">
					<h2><img src="images/home/icon-devices.gif" alt="" width="38" height="34" class="icon" />How do I use it?</h2>
					<p class="nospace">To use the MIT Mobile Web, you'll need:</p>
					
					<ol>
						<li><strong>A web-capable mobile device:</strong> 
							<ul>
								<li>iPhone or iPod Touch</li>
								<li>Smartphone (BlackBerry, Windows Mobile, Android, Pre/Pixi, etc.)</li>
								<li>Most recent 'feature' phones (including flip, slider or bar phones like the RAZR, Chocolate, Sync, etc.)</li>
							</ul>
						</li>
						<li><strong>A network connection:</strong> A web/data plan from your carrier<strong><a href="#disclaimer">*</a></strong> or a WiFi connection if your device has WiFi.</li>
					</ol>
						
					<p class="nospace"><strong>To get to the MIT Mobile Web:</strong> On your mobile device, launch your web browser and go to:
					</p>
					
					<p class="address"><a href="http://m.mit.edu/">m.mit.edu</a></p> 
					
					<p>Note: do <em>not</em> use 'www' in the web address.</p>
					
					<p>Your device's browser may be found under 'MediaNET' (AT&amp;T), 'Get It Now &gt; News &amp; Info' (Verizon Wireless), 'Power Vision' (Sprint), 'Safari' (iPhone/iPod Touch), 'Internet Explorer' (Windows Mobile), 'Opera' (some feature phones and smartphones), or just 'Web' or 'Browser'.</p>
					
					<p class="nospace">The MIT Mobile Web will automatically detect your device type and deliver an experience optimized for it.</p>
				</div>
				
				<div id="feedback" onclick="location.href='mailto:&#109;&#111;&#98;&#105;&#119;&#101;&#98;&#64;&#109;&#105;&#116;&#46;e&#100;&#117;'">
					<h2><img src="images/home/icon-feedback.gif" alt="" width="38" height="34" class="icon"/>We want to hear from you!</h2>
					<p class="nospace">Your suggestions will help us to continually improve this exciting new service. Click here to send us feedback at <a href="mailto:&#109;&#111;&#98;&#105;&#119;&#101;&#98;&#64;&#109;&#105;&#116;&#46;e&#100;&#117;">&#109;o&#98;&#105;&#119;e&#98;&#64;&#109;i&#116;&#46;&#101;d&#117;</a>.</p>
				</div>
				
			</div>
			
		</div>
	</div>
  </div>
</body>
</html>
