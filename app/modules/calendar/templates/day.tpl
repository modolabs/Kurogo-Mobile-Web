{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>
    {$feedTitle} for 
    {block name="date"}
      {$current|date_format:"%a %b %e, %Y"}
    {/block}
  </h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    <a href="{$prevURL}">
      &lt; {$prev|date_format:"%a %b %e"}
    </a> | 
    <a href="{$nextURL}">
      {$next|date_format:"%a %b %e"} &gt;
    </a>
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/templates/results.tpl" results=$events noResultsText="No Events Found"}

{$sideNav}

{include file="findInclude:common/templates/footer.tpl"}
