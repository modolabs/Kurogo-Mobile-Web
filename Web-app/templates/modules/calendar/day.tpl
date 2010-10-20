{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>
    {$type|capitalize} for 
    {block name="date"}
      {$current|date_format:"%a %b %e, %Y"}
    {/block}
  </h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    <a href="{$prevUrl}">
      &lt; {$prev|date_format:"%a %b %e"}
    </a> | 
    <a href="{$nextUrl}">
      {$next|date_format:"%a %b %e"} &gt;
    </a>
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/results.tpl" results=$events noResultsText="No Events Found"}

{$sideNav}

{include file="findInclude:common/footer.tpl"}
