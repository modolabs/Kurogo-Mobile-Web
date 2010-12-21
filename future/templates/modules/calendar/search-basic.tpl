{extends file="findExtends:modules/$moduleID/search.tpl"}

{block name="resultCount"}
  {if count($events)}
    <p>{count($events)} result{if count($events) != 1}s{/if} found</p>
  {/if}
{/block}
