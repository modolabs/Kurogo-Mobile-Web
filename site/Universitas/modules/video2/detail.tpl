{include file="findInclude:common/header.tpl"}

<h1 class="focal videoTitle">{$videoTitle}</h1>


<p class="nonfocal">
    <script type="text/javascript" src="http://admin.brightcove.com/js/BrightcoveExperiences.js"></script>
	<script src="http://brightcove-swf-hosting.s3.amazonaws.com/MobileCompatibility.js" type="text/javascript"></script>
	<object id="myExperience{$videoid}" class="BrightcoveExperience">
	  <param name="bgcolor" value="#FFFFFF" />
	  <param name="width" value="298" />
	  <param name="height" value="200" />
	  <param name="playerID" value="63793987001" />
	  <param name="publisherID" value="1079084864"/>
	  <param name="isVid" value="true" />
	  <param name="isUI" value="true" />
	  <param name="optimizedContentLoad" value="true" />
	  <param name="videoSmoothing" value="true" />	
	  <param name="@videoPlayer" value="{$videoid}" />
	</object>
	<script type="text/javascript">
	  runMobileCompatibilityScript('myExperience{$videoid}');
	</script>
</p>

<p class="focal">{$videoDescription}</p>

{include file="findInclude:common/footer.tpl"}