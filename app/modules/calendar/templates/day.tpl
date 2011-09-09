{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>
    {$feedTitle} for 
    {block name="date"}
      {$current|date_format:"%a %b %e, %Y"}
    {/block}
  </h2>
</div>

{include file="findInclude:modules/calendar/templates/viewlist.tpl" viewlist=$viewlist}

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav2{/block}">
    <a href="{$prevURL}" class="sidenav-prev">
      {$prev|date_format:"%a %b %e"}
    </a>
    <a href="{$nextURL}" class="sidenav-next">
      {$next|date_format:"%a %b %e"}
    </a>
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/templates/results.tpl" results=$events noResultsText="No Events Found"}

{$sideNav}

{include file="findInclude:common/templates/footer.tpl"}
