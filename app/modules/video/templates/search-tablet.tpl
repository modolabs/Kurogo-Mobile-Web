{extends file="findExtends:modules/video/templates/search.tpl"}

{block name="videoHeader"}
  <div id="videoHeader" class="splitview-header">
    {$smarty.block.parent}
  </div>
{/block}


{block name="videos"}
  <div id="tabletVideos" class="splitview">
    <div id="videos" class="listcontainer">
      {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
    </div>
    <div id="videoDetailWrapper" class="splitview-detailwrapper">
      <div id="videoDetail">
      </div><!-- videoDetail -->
    </div><!-- videoDetailWrapper -->
  </div><!-- tabletVideos -->
{/block}
