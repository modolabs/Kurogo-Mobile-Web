{extends file="findExtends:modules/news/templates/search.tpl"}

{block name="newsHeader"}
  <div id="newsHeader" class="splitview-header">
    {include file="findInclude:common/templates/search.tpl" extraArgs=$extraArgs}
  </div>
{/block}

{block name="stories"}
  {capture name="splitviewList" assign="splitviewList"}
    {include file="findInclude:modules/news/templates/stories.tpl"}
  {/capture}
  {$splitview = array()}
  {$splitview['id'] = "tabletNews"}
  {$splitview['class'] = "splitview-stories"}
  {$splitview['list'] = $splitviewList}
  {include file="findInclude:common/templates/splitview.tpl" splitview=$splitview}
{/block}
