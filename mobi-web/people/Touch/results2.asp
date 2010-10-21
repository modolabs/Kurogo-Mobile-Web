<!--#include file="../../include/touch/head.asp"-->

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
	<% Response.write(metaview) %>
	<meta name="HandheldFriendly" content="True" />	
	<meta name="MobileOptimized" content="240">
	<title>People Directory: Search Results</title>
	<link rel="stylesheet" type="text/css" href="../../styles/touch.css" />
	<style type="text/css">
	<% Response.write(extracss) %>
	</style>
</head>

<body>

<div id="navbar">	
	<a name="top" href="../../home/touch/index.asp"><img src="../../images/touch/mit-logo4.gif" width="48" height="28" alt="MIT" border="0" align="absmiddle" /></a><a href="index.asp"><img src="../../images/touch/title-people2.gif" width="42" height="28" alt="People" border="0" align="absmiddle" /></a><span>Search&nbsp;Results</span>
</div>

<div id="container">
		
	<div class="nonfocal">
		<form method="get" action="results.asp">
			<table cellpadding="0" cellspacing="0" border="0" style="width:100%"><tr ><td width="70%"><input type="text" id="query" name="q" size="14" class="forminput" value="lee"/></td><td width="30%"><input type="submit" value="Search" class="submitbutton" /></td></tr></table>
			<input id="type" name="type" type="hidden" value="name" />
			<input id="group" name="group" type="hidden" value="*" />
			<p>9 matches found.</p>
		</form>
	</div>
	
	
	<ul class="results">
		<li><a href="detail.asp">Christina Kathleen <strong>Beaumier</strong></a></li>
		
		<li><a href="detail.asp">Christalee R <strong>Bieber</strong></a></li>
		
		<li><a href="detail.asp">Christopher Lee <strong>Douglas</strong></a></li>
		
		<li><a href="detail.asp">Christopher Lee <strong>Frank</strong></a></li>
		
		<li><a href="detail.asp">Chris P <strong>Lee</strong></a></li>
		
		<li><a href="detail.asp">Chris Joong-Jae <strong>Lee</strong></a></li>
		
		<li><a href="detail.asp">Christine M <strong>Lee</strong></a></li>
		
		<li><a href="detail.asp">Christopher Lee <strong>Sansam</strong></a></li>
		
		<li><a href="detail.asp">Christopher Lee <strong>Waters</strong></a></li>
	
	</ul>
	
</div> <!-- id="container" -->

<div id="footerlinks">
	<a href="#top">Back to top</a> | <a href="help.asp">Help</a> | <a href="../../home/touch/index.asp">MIT Mobile Web</a>
</div>

<div id="footer">
	<a href="http://web.mit.edu/ist/"><img src="../../images/touch/ist-logo.gif" width="26" height="18" alt="IST" /></a>Information Services &amp; Technology
</div>

</body>
</html>
