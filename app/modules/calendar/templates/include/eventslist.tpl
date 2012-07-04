{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplateFile=$listItemTemplateFile|default:$defaultTemplateFile}
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
  {$title|escape}{if $date || $isToday}: 
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

<ul class="results"{if $resultslistID} id="{$resultslistID}"{/if}>
  {if $previousEventsURL}
    <li class="pagerlink">
      <a href="{$previousEventsURL}">{"PREVIOUS_EVENT_TEXT"|getLocalizedString:$maxPerPage}</a>
    </li>
  {/if}
  {foreach $events as $item}
    {if !isset($item['separator'])}
      <li{if $item['img']} class="icon"{/if}>
        {include file="$listItemTemplateFile" subTitleNewline=$subTitleNewline|default:true}
      </li>
    {/if}
  {/foreach}
  {if count($events) == 0}
    {block name="noResults"}
      <li>{"NO_RESULTS"|getLocalizedString}</li>
    {/block}
  {/if}
  
  {if $nextEventsURL}
    <li class="pagerlink">
      <a href="{$nextEventsURL}">{"NEXT_EVENT_TEXT"|getLocalizedString:$maxPerPage}</a>
    </li>
  {/if}
</ul>

{$sideNav}
