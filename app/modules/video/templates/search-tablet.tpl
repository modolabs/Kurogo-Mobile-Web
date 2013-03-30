{extends file="findExtends:modules/video/templates/search.tpl"}

{block name="videoHeader"}
  <div id="videoHeader" class="splitview-header">
    {$smarty.block.parent}
  </div>
{/block}


{block name="videos"}
  {capture name="splitviewList" assign="splitviewList"}
    {include file="findInclude:modules/$moduleID/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
  {/capture}
  {$splitview = array()}
  {$splitview['id'] = "tabletVideos"}
  {$splitview['class'] = "splitview-stories"}
  {$splitview['list'] = $splitviewList}
  {include file="findInclude:common/templates/splitview.tpl" splitview=$splitview}
{/block}
