<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
<!--[if lt IE 9]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
  {if $session_max_idle}
    <meta http-equiv="refresh" content="{$session_max_idle+2}" />
  {/if}
  <title>{$moduleName}{if !$isModuleHome}: {$pageTitle}{/if}</title>
  <link rel="shortcut icon" href="/favicon.ico" />
  <link href="{$minify['css']}" rel="stylesheet" media="all" type="text/css"/>
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
  {foreach $cssURLs as $cssURL}
    <link href="{$cssURL}" rel="stylesheet" media="all" type="text/css"/>
  {/foreach}
  <link href="/modules/admin/admin.css" rel="stylesheet" media="all" type="text/css"/>
</head>
<body>
<div id="pagewrap">
<header>
	<img src="/modules/admin/images/kurogo-logo.png" alt="Kurogo" width="90" height="90" id="logo" />
	<h1>	
		Kurogo&trade; Adminstration Console: 
		<span id="sitename">{$SITE_NAME}</span>
	</h1>
	<div id="utility">
		<div id="user">Signed in as <span id="username">Pete Akins</span><a id="signout" href="" onclick="confirmSignout()">Sign out</a></div>
	</div>
</header>

<div id="contentwrap">
	<nav>
		<ul>
			<li>
				<a href="">Site Configuration</a>
				<ul>
					<li><a class="current" href="setup.html">Site Setup</a></li>
					<li><a href="">Default Modules</a></li>
					<li><a href="">Theme</a></li>
					<li><a href="">Device Detection</a></li>
					<li><a href="error.html">Error Handling and Debugging</a></li>
					<li><a href="">Database</a></li>
					<li><a href="">Cookies</a></li>
					<li><a href="">Authentication</a></li>
					<li><a href="">Analytics</a></li>
					<li><a href="">Log Files and Temp Directory</a></li>
				</ul>
			</li>
			<li>
				<a href="">Module Configuration</a>
			</li>
			<li>
				<a href="">Text Strings</a>
			</li>
		</ul>
	</nav>
	
	<div id="content">
		<form method="post">
			<input name="submit" id="submit" type="submit" value="Save" />
			<h1>Module Overview</h1>
			<table class="configtable">
				<thead>
					<tr>
						<th colspan="2">Module name</th>
						<th>ID</th>
						<th>Enabled</th>
						<th>Protected</th>
						<th>Searchable</th>
						<th>Secure</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><img src="/modules/home/images/compliant/about.png" width="30" height="30" alt="" /></td>
						<td><a href="module-about.html">About</a></td>
						<td>about</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/admin.png" width="30" height="30" alt="" /></td>
						<td><a href="module-admin.html">Admin</a></td>
						<td>admin</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/calendar.png" width="30" height="30" alt="" /></td>
						<td><a href="calendar.html">Calendar</a></td>
						<td>calendar</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/content.png" width="30" height="30" alt="" /></td>
						<td><a href="content.html">Content (Static)</a></td>
						<td>content</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/configure.png" width="30" height="30" alt="" /></td>
						<td><a href="configure.html">Configure Homescreen</a></td>
						<td>configure</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/download.png" width="30" height="30" alt="" /></td>
						<td><a href="download.html">Download</a></td>
						<td>download</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/emergency.png" width="30" height="30" alt="" /></td>
						<td><a href="emergency.html">Emergency Info</a></td>
						<td>emergency</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/error.png" width="30" height="30" alt="" /></td>
						<td><a href="error.html">Error</a></td>
						<td>error</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/fullweb.png" width="30" height="30" alt="" /></td>
						<td><a href="fullweb.html">Full Website (Link)</a></td>
						<td>fullweb</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/home.png" width="30" height="30" alt="" /></td>
						<td><a href="home.html">Home</a></td>
						<td>home</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/indoorMaps.png" width="30" height="30" alt="" /></td>
						<td><a href="indoormaps.html">Indoor Maps</a></td>
						<td>indoorMaps</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/info.png" width="30" height="30" alt="" /></td>
						<td><a href="info.html">Info</a></td>
						<td>info</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/links.png" width="30" height="30" alt="" /></td>
						<td><a href="links.html">Links</a></td>
						<td>links</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/login.png" width="30" height="30" alt="" /></td>
						<td><a href="login.html">Login</a></td>
						<td>login</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/map.png" width="30" height="30" alt="" /></td>
						<td><a href="map.html">Map</a></td>
						<td>map</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/news.png" width="30" height="30" alt="" /></td>
						<td><a href="news.html">News</a></td>
						<td>news</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/people.png" width="30" height="30" alt="" /></td>
						<td><a href="people.html">People Directory</a></td>
						<td>people</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
					<tr>
						<td><img src="/modules/home/images/compliant/stats.png" width="30" height="30" alt="" /></td>
						<td><a href="stats.html">Statistics</a></td>
						<td>stats</td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
						<td><input type="checkbox" /></td>
					</tr>
				</tbody>
			</table>
		</form>		
	</div>

</div> <!-- id="contentwrap" -->

<footer>
	<a href="http://www.modolabs.com"><img src="/modules/admin/images/modo-logo.png" alt="Modo Labs, Inc." width="50" height="50" id="footerlogo" />&copy;2011 Modo Labs, Inc. All rights reserved.</a>
</footer>
</div> <!-- id="pagewrap" -->
</body>
</html>
