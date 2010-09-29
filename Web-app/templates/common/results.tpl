{strip}

{block name="resultsStart"}
  <ul class="results">
{/block}

    {foreach $results as $index => $item}
  
      {capture name="itemHTML" assign="itemHTML"}
        {$item['title']}
        {if isset($item['subtitle'])}
          <br/>
          <span class="smallprint">{$item['subtitle']}</span>
        {/if}
      {/capture}
  
      {block name="resultsItem"}
        <li>
          {if isset($item['url'])}
            <a href="{$item['url']}" class="{$item['class']|default:''}">
          {/if}
              {$itemHTML}
          {if isset($item['url'])}
            </a>
          {/if}
        </li>
      {/block}
  
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
