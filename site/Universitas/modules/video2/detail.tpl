{include file="findInclude:common/header.tpl"}

<h1 class="focal videoTitle">{$videoTitle}</h1>

<p class="nonfocal">

    <script type="text/javascript" src="http://admin.brightcove.com/js/BrightcoveExperiences.js"></script>
	<script src="http://brightcove-swf-hosting.s3.amazonaws.com/MobileCompatibility.js" type="text/javascript"></script>

<!--
	<object id="myExperience{$videoid}" class="BrightcoveExperience">
	  <param name="bgcolor" value="#FFFFFF" />
	  <param name="width" value="298" />
	  <param name="height" value="200" />
	  <param name="playerID" value="{$playerid}" />
	  <param name="publisherID" value="{$accountid}"/>
	  <param name="isVid" value="true" />
	  <param name="isUI" value="true" />
	  <param name="optimizedContentLoad" value="true" />
	  <param name="videoSmoothing" value="true" />	
	  <param name="@videoPlayer" value="{$videoid}" />
	</object>
	
	<object id="myPlayer" class="BrightcoveExperience">
		<param name="bgcolor" value="#FFFFFF" />
		<param name="width" value="486" />
		<param name="height" value="412" />
		<param name="playerID" value="{$playerid}" />
		<param name="isVid" value="true" />
	</object>
-->
	<script src="http://admin.brightcove.com/js/APIModules_all.js"> </script> 
	 <object id="myExperience" class="BrightcoveExperience">
	    <param name="bgcolor" value="#FFFFFF" />
	    <param name="width" value="250" />
	    <param name="height" value="250" />
	    <param name="playerID" value="{$playerid}" />
	    <param name="publisherID" value="{$accountid}"/>
	    <param name="@videoPlayer" value="{$videoid}" />
	    <param name="isVid" value="true" />
	    <param name="isUI" value="true" />
	 </object>
 
	<script type="text/javascript">
	  runMobileCompatibilityScript('myExperience{$videoid}');
	</script>

</p>

<p class="focal">{$videoDescription}</p>

{include file="findInclude:common/footer.tpl"}