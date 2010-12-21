{extends file="findExtends:modules/calendar/category.tpl"}

{block name='sideNavClass'}nonfocal{/block}

{block name="resultCount"}
  <p>{count($events)} result{if count($events) != 1}s{/if} found</p>
{/block}
