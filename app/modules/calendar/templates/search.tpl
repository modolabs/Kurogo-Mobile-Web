{include file="findInclude:common/templates/header.tpl"}


{capture name="selectSection" assign="selectSection"}
  <select id="calendars" name="calendar">
  {foreach $feeds as $type=>$typeFeeds}
  <optgroup label="{$type}">
  {foreach $typeFeeds as $feed=>$title}
      <option value="{$feed}"{if $searchCalendar==$feed} selected{/if}>in {$title|escape}</option>
  {/foreach}
  </optgroup>
  {/foreach}
  </select>
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

{include file="findInclude:common/templates/results.tpl" results=$events noResultsText="No Events Found"}

{include file="findInclude:common/templates/footer.tpl"}
