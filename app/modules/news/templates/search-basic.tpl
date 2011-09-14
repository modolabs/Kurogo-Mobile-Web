{extends file="findExtends:modules/news/templates/search.tpl"}

{block name="resultCount"}
  {if count($stories)}
    <p>{count($stories)} result{if count($stories) != 1}s{/if} found</p>
  {/if}
{/block}
