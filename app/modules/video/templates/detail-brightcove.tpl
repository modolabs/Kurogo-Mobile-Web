{extends file="findExtends:modules/video/templates/detail.tpl"}

{block name="videoPlayer"}
    <script type="text/javascript" src="http://admin.brightcove.com/js/BrightcoveExperiences.js"></script>
	<script src="http://brightcove-swf-hosting.s3.amazonaws.com/MobileCompatibility.js" type="text/javascript"></script>

	 <script src="http://admin.brightcove.com/js/APIModules_all.js"> </script> 
	 <object id="myExperience" class="BrightcoveExperience">
	    <param name="bgcolor" value="#FFFFFF" />
	    <param name="width" value="300" />
	    <param name="height" value="250" />
	    <param name="playerID" value="{$feedData.playerId}" />
	    <param name="playerKey" value="{$feedData.playerKey}" />  
	    <param name="@videoPlayer" value="{$videoid}" />
	    <param name="isVid" value="true" />
	    <param name="isUI" value="true" />
	 </object>

	<script type="text/javascript">
	  runMobileCompatibilityScript('myExperience{$videoid}');
	</script>
{/block}