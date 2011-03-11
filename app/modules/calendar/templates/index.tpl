{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{$today|date_format:"%A %b %e, %Y"}</h2>
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
  <select id="calendars" name="calendar">
  {foreach $feeds as $type=>$typeFeeds}
  <optgroup label="{$type}">
  {foreach $typeFeeds as $feed=>$title}
      <option value="{$feed}">in {$title|escape}</option>
  {/foreach}
  </optgroup>
  {/foreach}
  </select>
  <select id="timeframe" name="timeframe">
    {foreach $searchOptions as $key => $option}
      <option value="{$key}"{if isset($option['selected']) && $option['selected']} selected="selected"{/if} >
        {$option['phrase']}
      </option>
    {/foreach}
  </select>
{/capture}

{include file="findInclude:common/templates/search.tpl" additionalInputs=$selectSection placeholder="Search for events"}

{include file="findInclude:common/templates/footer.tpl"}
