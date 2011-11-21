{extends file="findExtends:modules/video/templates/bookmarks.tpl"}

{block name="bookmarkHeader"}
  <div id="videoHeader" class="splitview-header"></div>
{/block}

{block name="bookmarks"}
  <div id="tabletVideos" class="splitview">
    <div id="videos" class="listcontainer">
      {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
    </div>
    <div id="videoDetailWrapper" class="splitview-detailwrapper">
      <div id="videoDetail">
      </div><!-- videoDetail -->
    </div><!-- videoDetailWrapper -->
  </div><!-- tabletVideos -->
  <div id="noBookmarks" class="nonfocal" style="display:none">
    No bookmarked videos
  </div>
{/block}
