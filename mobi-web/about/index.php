<?php


require WEBROOT . '/mobile-about/WhatsNew.inc';
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
		// Launches a new, small browser window containing the <?= SITE_NAME ?> in preview mode for desktop and laptop computers
			var popup = window.open('preview.html','popwin',
'left=20,top=20,innerwidth=260,innerheight=320,toolbar=0,menubar=0,location=0,status=1,resizable=1,scrollbars=yes');
		}	
	//-->
	</script>
</head>
<body>
    <p>
	    <a id="preview" href="preview.html" onclick="preview(); return false;">Click here to preview the site on your desktop or laptop.</a>
    </p>
    <p>
    Waiting on copy
    </p>
</body>
</html>
