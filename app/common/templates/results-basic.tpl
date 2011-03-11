{if $accessKey|default:false}
  {html_access_key_reset index=$accessKeyStart}
{/if}
<div class="focal">
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <p>{include file="findInclude:common/templates/listItem.tpl" accessKey=$accessKey|default:false}</p>
    {/if}
  {/foreach}    
  {if count($results) == 0}
    <span>{$noResultsText|default:"No results found"}</span>
  {/if}
</div>
