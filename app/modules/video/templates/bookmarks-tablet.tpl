{extends file="findExtends:modules/video/templates/bookmarks.tpl"}

{block name="bookmarkHeader"}
  <div id="videoHeader" class="splitview-header"></div>
{/block}

{block name="bookmarks"}
  {capture name="splitviewList" assign="splitviewList"}
    {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
  {/capture}
  {$splitview = array()}
  {$splitview['id'] = "tabletVideos"}
  {$splitview['class'] = "splitview-stories"}
  {$splitview['list'] = $splitviewList}
  {include file="findInclude:common/templates/splitview.tpl" splitview=$splitview}
  <div id="noBookmarks" class="nonfocal" style="display:none">
    No bookmarked videos
  </div>
{/block}
