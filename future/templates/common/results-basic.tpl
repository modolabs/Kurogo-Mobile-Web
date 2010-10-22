{extends file="findExtends:common/results.tpl"}

{block name="resultsStart"} 
  {if $accessKey|default:false}
    {html_access_key_reset index=$accessKeyStart}
  {/if}
  <div class="focal">
{/block}

    {block name="resultsItem"}
      <p>
        {include file="findInclude:common/listItem.tpl" accessKey=$accessKey|default:false}
      </p>
    {/block}

    {block name="noResults"}
      <span>{$noResultsText|default:"No results found"}</span>
    {/block}

{block name="resultsEnd"}
  </div>
{/block}
