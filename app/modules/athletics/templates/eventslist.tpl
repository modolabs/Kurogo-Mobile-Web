{capture name="sideNav" assign="sideNav"}
{if $prevURL || $nextURL}
  <div class="{block name='sideNavClass'}sidenav2{/block}">
    {if $prevURL && $prev}
      <a href="{$prevURL}" class="sidenav-prev">
        {block name="prevPrefix"}{/block}
        {if $linkDateFormat}
          {$prev|date_format:$linkDateFormat}
        {else}
          {$prev}
        {/if}
      </a>{block name="sidenavSpacer"} {/block}
    {/if}
    {if $nextURL && $next}
      <a href="{$nextURL}" class="sidenav-next">
        {if $linkDateFormat}
          {$next|date_format:$linkDateFormat}
        {else}
          {$next}
        {/if}
        {block name="nextSuffix"}{/block}
      </a>
    {/if}
  </div>
{/if}
{/capture}

{capture name="fullTitle" assign="fullTitle"}
  {$title}{if $date || $isToday}: 
    {block name="date"}
      {if $isToday}
        Today
      {else}
        {$date|date_format:$titleDateFormat}
      {/if}
    {/block}
  {/if}
{/capture}

{block name="navheader"}
  <div class="nonfocal">
    <h2>{$fullTitle}</h2>
  </div>
  {$sideNav}
{/block}

{block name="resultsCount"}{/block}

{include file="findInclude:common/templates/results.tpl" results=$events}

{$sideNav}
