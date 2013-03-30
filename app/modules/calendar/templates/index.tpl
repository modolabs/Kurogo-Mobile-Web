{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$today|date_format:$dateFormat}</h2>
</div>

{if $upcomingEvents} 
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$upcomingEvents subTitleNewline=true}
{/if}

{if count($userCalendars)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$userCalendars}
{/if}

{if count($resources)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$resources}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$calendarPages}

{capture name="selectSection" assign="selectSection"}
  {if $totalFeeds>1}
    <select id="calendars" name="calendar">
    {foreach $feeds as $type=>$typeFeeds}
      {if $feeds|@count>1}
        <optgroup label="{$type}">
      {/if}
        {foreach $typeFeeds as $feed=>$title}
          <option value="{$feed}"{if $selectedFeed==$feed} selected="selected"{/if}>{"CALENDAR_SEARCH_IN"|getLocalizedString:$title}</option>
        {/foreach}
      {if $feeds|@count>1}
        </optgroup>
      {/if}
    {/foreach}
    </select>
  {elseif strlen($selectedFeed)}
    <input type="hidden" name="calendar" value="{$selectedFeed}" />
  {/if}
  <select id="timeframe" name="timeframe">
    {foreach $searchOptions as $key => $option}
      <option value="{$key}"{if isset($option['selected']) && $option['selected']} selected="selected"{/if} >
        {$option['phrase']}
      </option>
    {/foreach}
  </select>
{/capture}

{if count($categories)}
{block name="categoryList"}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$categories navListHeading=$categoryHeading}
{/block}
{/if}

<div class="{if $totalFeeds>1}select-count-2{else}select-count-1{/if}">
{include file="findInclude:common/templates/search.tpl" additionalInputs=$selectSection}
</div>

{include file="findInclude:common/templates/footer.tpl"}
