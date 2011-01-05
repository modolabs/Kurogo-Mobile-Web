{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


<div class="nonfocal">
  <h2>{$today|date_format:"%A %b %e, %Y"}</h2>
</div>

{block name="navList"}
  {include file="findInclude:common/navlist.tpl" navlistItems=$calendarPages}
{/block}

{capture name="selectSection" assign="selectSection"}
  <select id="timeframe" name="timeframe">
    {foreach $searchOptions as $key => $option}
      <option value="{$key}"{if isset($option['selected']) && $option['selected']} selected="selected"{/if} >
        {$option['phrase']}
      </option>
    {/foreach}
  </select>
{/capture}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" additionalInputs=$selectSection placeholder="Search for events"}
{/block}

{include file="findInclude:common/footer.tpl"}
