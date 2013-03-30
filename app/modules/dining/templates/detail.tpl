{include file="findInclude:common/templates/header.tpl"}

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
    {block name="date"}
    <span class="sidenav-current nonfocal">
      {if $isToday}
        Today
      {else}
        {$current|date_format:$titleDateFormat}
      {/if}
      </span>
    {/block}

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


<div class="nonfocal">
  {block name="title"}
    <h2>{$title}</h2>
    <p>{$description}</p>
  {/block}
</div>
{if $location}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$location}
{/if}

{$sideNav}
{block name="events"}
{if count($events)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$events navlistID="locations" accessKey=false subTitleNewline=true}
{/if}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
