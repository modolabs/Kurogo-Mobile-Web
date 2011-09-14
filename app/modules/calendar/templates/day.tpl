{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>
    {$feedTitle}: {block name="date"}
      {$current|date_format:$titleDateFormat}
    {/block}
  </h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav2{/block}">
    <a href="{$prevURL}" class="sidenav-prev">
      {$prev|date_format:$linkDateFormat}
    </a>
    <a href="{$nextURL}" class="sidenav-next">
      {$next|date_format:$linkDateFormat}
    </a>
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/templates/results.tpl" results=$events}

{$sideNav}

{include file="findInclude:common/templates/footer.tpl"}
