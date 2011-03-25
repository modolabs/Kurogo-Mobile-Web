<ul class="results">
  {$ellipsisCount=0}
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <li class="video{if $item['img']} noimage{/if}">  
  
        {include file="findInclude:modules/$moduleID/templates/listItem.tpl" ellipsisId=$ellipsisCount++ subTitleNewline=$subTitleNewline|default:true} 
       
        {*
        <div class="ellipsis" id="ellipsis_{$ellipsisCount++}">
      	{include file="findInclude:modules/$moduleID/templates/listItem.tpl" subTitleNewline=$subTitleNewline|default:true} 
        </div>
        *}	
      </li>
    {/if}
  {/foreach}
  {if count($results) == 0}
    {block name="noResults"}
      <li>{$noResultsText|default:"No results found"}</li>
    {/block}
  {/if}
</ul>
