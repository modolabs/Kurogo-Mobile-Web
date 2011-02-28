<ul class="results"{if $resultslistID} id="{$resultslistID}"{/if}>
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <li{if $item['img']} class="icon"{/if}>
        {include file="findInclude:common/listItem.tpl" subTitleNewline=$subTitleNewline|default:true}
      </li>
    {/if}
  {/foreach}
  {if count($results) == 0}
    {block name="noResults"}
      <li>{$noResultsText|default:"No results found"}</li>
    {/block}
  {/if}
</ul>
