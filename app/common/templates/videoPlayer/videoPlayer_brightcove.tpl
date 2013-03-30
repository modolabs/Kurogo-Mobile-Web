<!-- Start of Brightcove Player -->

<div style="display:none">

</div>

<!--
By use of this code snippet, I agree to the Brightcove Publisher T and C 
found at https://accounts.brightcove.com/en/terms-and-conditions/. 
-->

<script language="JavaScript" type="text/javascript" src="http://admin.brightcove.com/js/BrightcoveExperiences.js"></script>

<object id="myExperience{$video->getID()}" class="BrightcoveExperience kgo-videoplayer-object">
  <param name="bgcolor" value="#FFFFFF" />
  <param name="width" value="{$video->getWidth()}" />
  <param name="height" value="{$video->getHeight()}" />
  <param name="playerID" value="{$video->getPlayerId()}" />
  <param name="playerKey" value="{$video->getPlayerKey()}" />
  <param name="isVid" value="true" />
  <param name="isUI" value="true" />
  <param name="dynamicStreaming" value="true" />
  
  <param name="@videoPlayer" value="{$video->getID()}" />
</object>

<!-- 
This script tag will cause the Brightcove Players defined above it to be created as soon
as the line is read by the browser. If you wish to have the player instantiated only after
the rest of the HTML is processed and the page load is complete, remove the line.
-->
<script type="text/javascript">brightcove.createExperiences();</script>

<!-- End of Brightcove Player -->
