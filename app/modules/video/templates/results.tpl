<ul class="results"{if $resultsID} id="{$resultsID}"{/if}>
  {$ellipsisCount=0}
  {if $previousURL}
    <li class="pagerlink">
      <a href="{$previousURL}">{"PREVIOUS_VIDEO_TEXT"|getLocalizedString:$maxPerPage}</a>
    </li>
  {/if}
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <li class="video{if !$item['img']} noimage{/if}">  
  
        {include file="findInclude:modules/$moduleID/templates/listItem.tpl" ellipsisId=$ellipsisCount++ subTitleNewline=$subTitleNewline|default:true} 
       
      </li>
    {/if}
  {/foreach}
  {if count($results) == 0}
    {block name="noResults"}
      <li>{"NO_RESULTS"|getLocalizedString}</li>
    {/block}
  {/if}
  {if $nextURL}
    <li class="pagerlink">
      <a href="{$nextURL}">{"NEXT_VIDEO_TEXT"|getLocalizedString:$maxPerPage}</a>
    </li>
  {/if}
</ul>
