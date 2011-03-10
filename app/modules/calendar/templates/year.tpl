{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$feedTitle} for {$current}</h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    {if $prev}
      <a href="{$prevURL}">&lt; {$prev}</a> 
      {if $next}|{/if} 
    {/if}
    {if $next}
      <a href="{$nextURL}">{$next} &gt;</a>
    {/if}
  </div>
{/capture}

{$sideNav}

{include file="findInclude:common/templates/results.tpl" results=$events}

{$sideNav}

{include file="findInclude:common/templates/footer.tpl"}
