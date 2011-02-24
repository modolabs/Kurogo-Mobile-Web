{include file="findInclude:common/header.tpl"}


{capture name="selectSection" assign="selectSection"}
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

{include file="findInclude:common/search.tpl" additionalInputs=$selectSection resultCount=$resultCount}

{include file="findInclude:common/results.tpl" results=$events noResultsText="No Events Found"}

{include file="findInclude:common/footer.tpl"}
