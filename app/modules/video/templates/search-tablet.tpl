{extends file="findExtends:modules/video/templates/search.tpl"}

{block name="videoHeader"}
  <div id="videoHeader">
    {$smarty.block.parent}
  </div>
{/block}


{block name="videos"}
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
