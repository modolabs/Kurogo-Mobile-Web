<ul class="results">
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <li{if $item['img']} class="icon"{/if}>{include file="findInclude:common/listItem.tpl"}</li>
    {/if}
  {/foreach}
  {if count($results) == 0}
    {block name="noResults"}
      <li>{$noResultsText|default:"No results found"}</li>
    {/block}
  {/if}
</ul>
