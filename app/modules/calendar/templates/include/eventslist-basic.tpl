{extends file="findExtends:modules/calendar/templates/include/eventslist.tpl"}

{block name="date"}
  <strong>{$smarty.block.parent}</strong>
{/block}

{block name='sideNavClass'}nonfocal{/block}

{block name="prevPrefix"}&lt; {/block}
{block name="sidenavSpacer"}&nbsp;&nbsp;&nbsp;{/block}
{block name="nextSuffix"} &gt;{/block}

{block name="resultCount"}
  {if $showResultCount}
    <p>{count($events)} result{if count($events) != 1}s{/if} found</p>
  {/if}
{/block}
