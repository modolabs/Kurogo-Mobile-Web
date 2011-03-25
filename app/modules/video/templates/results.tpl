<ul class="results">
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <li class="video{if $item['img']} noimage{/if}">  
       {*
      	{include file="findInclude:common/templates/listItem.tpl" subTitleNewline=true}
        *}
      	{include file="findInclude:modules/$moduleID/templates/listItem.tpl" subTitleNewline=$subTitleNewline|default:true} 	
      </li>
    {/if}
  {/foreach}
  {if count($results) == 0}
    {block name="noResults"}
      <li>{$noResultsText|default:"No results found"}</li>
    {/block}
  {/if}
</ul>
