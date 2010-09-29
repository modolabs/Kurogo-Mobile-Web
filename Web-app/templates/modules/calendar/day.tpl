{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>
    {$Type} for {$current['day_3Let']} {$current['month_3Let']} {$current['day_num']}, {$current['year']}
  </h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="sidenav">
    <a href="{$prevUrl}">
      &lt; {$prev['day_3Let']} {$prev['month_3Let']} {$prev['day_num']}
    </a> | 
    <a href="{$nextUrl}">
      {$next['day_3Let']} {$next['month_3Let']} {$next['day_num']} &gt;
    </a>
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/results.tpl" results=$events noResultsText="No Events Found"}

{$sideNav}

{include file="findInclude:common/footer.tpl"}
