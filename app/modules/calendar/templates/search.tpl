{include file="findInclude:common/templates/header.tpl"}

{capture name="selectSection" assign="selectSection"}
{if $totalFeeds>1}
  <select id="calendars" name="calendar">
  {foreach $feeds as $type=>$typeFeeds}
  {if $feeds|@count>1}
  <optgroup label="{$type}">
  {/if}
  {foreach $typeFeeds as $feed=>$title}
      <option value="{$feed}"{if $searchCalendar==$feed} selected{/if}>in {$title|escape}</option>
  {/foreach}
  {if $feeds|@count>1}
  </optgroup>
  {/if}
  {/foreach}
  </select>
{elseif strlen($searchCalendar)}
<input type="hidden" name="calendar" value="{$searchCalendar}" />
{/if}
  <select id="timeframe" name="timeframe">
    {foreach $searchOptions as $key => $option}
      <option value="{$key}"{if $selectedOption == $key} selected="selected"{/if} >
        {$option['phrase']}
      </option>
    {/foreach}
  </select>
{/capture}

{$resultCount = count($events)}
{if !$resultCount}
  {$resultCount = null}
{/if}

{include file="findInclude:common/templates/search.tpl" additionalInputs=$selectSection resultCount=$resultCount}

{include file="findInclude:common/templates/results.tpl" results=$events}

{include file="findInclude:common/templates/footer.tpl"}
