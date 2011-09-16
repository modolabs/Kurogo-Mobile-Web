{extends file="findExtends:modules/video/templates/bookmarks.tpl"}

{block name="bookmarkHeader"}
  <div id="videoHeader"></div>
{/block}

{block name="bookmarks"}
  <div id="tabletVideos">
    <div id="videos">
      {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
    </div>
    <div id="videoDetailWrapper">
      <div id="videoDetail">
      </div><!-- videoDetail -->
    </div><!-- videoDetailWrapper -->
  </div><!-- tabletVideos -->
{/block}
