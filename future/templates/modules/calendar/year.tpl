{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$feedTitle} for {$current}</h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    {if $prev}
      <a href="{$prevUrl}">&lt; {$prev}</a> 
      {if $next}|{/if} 
    {/if}
    {if $next}
      <a href="{$nextUrl}">{$next} &gt;</a>
    {/if}
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/results.tpl" results=$events}

{$sideNav}

{include file="findInclude:common/footer.tpl"}
