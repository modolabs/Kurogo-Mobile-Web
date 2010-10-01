{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>
    {$category}: 
    {if $isToday}
      Today
    {else}
      {$current['day_3Let']} {$current['month_3Let']} {$current['day_num']}, {$current['year']}
    {/if}
  </h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    <a href="{$prevUrl}">
      &lt; {$prev['day_3Let']} {$prev['month_3Let']} {$prev['day_num']}
    </a> | 
    <a href="{$nextUrl}">
      {$next['day_3Let']} {$next['month_3Let']} {$next['day_num']} &gt;
    </a>
  </div>
{/capture}

{$sideNav}

{block name="resultCount"}{/block}

{include file="findInclude:common/results.tpl" results=$events noResultsText="No Events Found"}

{$sideNav}

{include file="findInclude:common/footer.tpl"}
