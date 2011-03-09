{include file="findInclude:common/header.tpl"}

<h1 class="focal videoTitle">{$videoTitle}
<div class="actionbutton"> 
  {include file="findInclude:common/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}  
</div>
<div class="actionbutton"> 
  {include file="findInclude:common/share.tpl" shareURL={$videoURL} shareRemark={$shareRemark} shareEmailURL={$shareEmailURL}}
</div>
</h1>
 
<p class="nonfocal">

    <script type="text/javascript" src="http://admin.brightcove.com/js/BrightcoveExperiences.js"></script>
	<script src="http://brightcove-swf-hosting.s3.amazonaws.com/MobileCompatibility.js" type="text/javascript"></script>

	 <script src="http://admin.brightcove.com/js/APIModules_all.js"> </script> 
	 <object id="myExperience" class="BrightcoveExperience">
	    <param name="bgcolor" value="#FFFFFF" />
	    <param name="width" value="300" />
	    <param name="height" value="250" />
	    <param name="publisherID" value="{$accountid}"/>
	    <param name="playerID" value="{$playerid}" />
	    <param name="playerKey" value="{$playerKey}" />  
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