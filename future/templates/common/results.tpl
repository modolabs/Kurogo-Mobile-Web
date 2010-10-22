{strip}

{block name="resultsStart"}
  <ul class="results">
{/block}

    {foreach $results as $item}
      {if !isset($item['separator'])}
  
        {block name="resultsItem"}
          <li{if $item['img']} class="icon"{/if}>
            {include file="findInclude:common/listItem.tpl"}
          </li>
        {/block}
  
      {/if}
    {/foreach}
    
    {if count($results) == 0}
      {block name="noResults"}
        <li>{$noResultsText|default:"No results found"}</li>
      {/block}
    {/if}

{block name="resultsEnd"}
  </ul>
{/block}

{/strip}
