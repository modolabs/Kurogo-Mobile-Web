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
			<h1>Site Setup</h1>
			<ul class="formfields">
				<li>
					<label for="site-name">Site name:</label>
					<input type="text" id="site-name" name="site-name" value="Universitas Mobile Web" />
					<span class="helptext">Name of this website as it appears in the website, including in the "Return to [site-name] home" link in the footer</span>
				</li>
				<li>
					<label for="organization-name">Organization name:</label>
					<input type="text" id="organization-name" name="organization-name" value="Universitas" />
					<span class="helptext">Name of the organization that owns this site. Appears in the title of the "About [organization-name]" link in the "About" module</span>
				</li>
				<li>
					<label for="language">Default language:</label>
					<select name="language" id="language">
						<option value="en" selected>English</option>
					</select>
					<span class="helptext">Sets the default text encoding for the entire site</span>
				</li>
				<li>
					<label for="time-zone">Local time zone:</label>
					<select name="time-zone" id="time-zone">
						<option value="-12.0">(GMT -12:00) Eniwetok, Kwajalein</option>
						<option value="-11.0">(GMT -11:00) Midway Island, Samoa</option>
						<option value="-10.0">(GMT -10:00) Hawaii</option>
						<option value="-9.0">(GMT -9:00) Alaska</option>
						<option value="-8.0">(GMT -8:00) Pacific Time (US &amp; Canada)</option>
						<option value="-7.0">(GMT -7:00) Mountain Time (US &amp; Canada)</option>
						<option value="-6.0">(GMT -6:00) Central Time (US &amp; Canada), Mexico City</option>
						<option value="-5.0" selected>(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima</option>
						<option value="-4.0">(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz</option>
						<option value="-3.5">(GMT -3:30) Newfoundland</option>
						<option value="-3.0">(GMT -3:00) Brazil, Buenos Aires, Georgetown</option>
						<option value="-2.0">(GMT -2:00) Mid-Atlantic</option>
						<option value="-1.0">(GMT -1:00 hour) Azores, Cape Verde Islands</option>
						<option value="0.0">(GMT) Western Europe Time, London, Lisbon, Casablanca</option>
						<option value="1.0">(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris</option>
						<option value="2.0">(GMT +2:00) Kaliningrad, South Africa</option>
						<option value="3.0">(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg</option>
						<option value="3.5">(GMT +3:30) Tehran</option>
						<option value="4.0">(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi</option>
						<option value="4.5">(GMT +4:30) Kabul</option>
						<option value="5.0">(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent</option>
						<option value="5.5">(GMT +5:30) Bombay, Calcutta, Madras, New Delhi</option>
						<option value="5.75">(GMT +5:45) Kathmandu</option>
						<option value="6.0">(GMT +6:00) Almaty, Dhaka, Colombo</option>
						<option value="7.0">(GMT +7:00) Bangkok, Hanoi, Jakarta</option>
						<option value="8.0">(GMT +8:00) Beijing, Perth, Singapore, Hong Kong</option>
						<option value="9.0">(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk</option>
						<option value="9.5">(GMT +9:30) Adelaide, Darwin</option>
						<option value="10.0">(GMT +10:00) Eastern Australia, Guam, Vladivostok</option>
						<option value="11.0">(GMT +11:00) Magadan, Solomon Islands, New Caledonia</option>
						<option value="12.0">(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka</option>
					</select>
				</li>
				<li>
					<label for="area-code">Local area code:</label>
					<input type="text" id="area-code" name="area-code" value="617" />
					<span class="helptext">Area code for local phone numbers. [Add note as to where this is used]</span>
				</li>
				<li class="checkitem">
					<label for="detect-phone">Enable auto-detection of phone numbers</label>
					<input type="checkbox" id="detect-phone" name="detect-phone" />
					<span class="helptext">On some web browsers (e.g., iOS), phone numbers are automatically detected and turned into tappable or clickable links. Uncheck this box to disable this behavior site-wide.</span>
				</li>
			</ul>
		</form>		
	</div>

</div> <!-- id="contentwrap" -->

<footer>
	<a href="http://www.modolabs.com"><img src="/modules/admin/images/modo-logo.png" alt="Modo Labs, Inc." width="50" height="50" id="footerlogo" />&copy;2011 Modo Labs, Inc. All rights reserved.</a>
</footer>
</div> <!-- id="pagewrap" -->
</body>
</html>
