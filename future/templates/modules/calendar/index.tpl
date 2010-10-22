{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$today|date_format:"%A %b %e, %Y"}</h2>
</div>

{strip}
  {$navlistItems = array()}
  
  {$navlistItems[0] = array()}
  {$navlistItems[0]['title'] = "Today's events"}
  {$navlistItems[0]['url']   = $todaysEventsUrl}
  
  {$navlistItems[1] = array()}
  {$navlistItems[1]['title'] = "Browse events by category"}
  {$navlistItems[1]['url']   = $categoriesUrl}
  
  {$navlistItems[2] = array()}
  {$navlistItems[2]['title'] = "Academic calendar"}
  {$navlistItems[2]['url']   = $academicUrl}
{/strip}

{include file="findInclude:common/navlist.tpl" navlistItems=$navlistItems}

{capture name="selectSection" assign="selectSection"}
  <select id="timeframe" name="timeframe">
    {foreach $searchOptions as $key => $option}
      <option value="{$key}"{if isset($option['selected']) && $option['selected']} selected="selected"{/if} >
        {$option['phrase']}
      </option>
    {/foreach}
  </select>
{/capture}

{include file="findInclude:common/search.tpl" additionalInputs=$selectSection placeholder="Search for events"}

{include file="findInclude:common/footer.tpl"}
