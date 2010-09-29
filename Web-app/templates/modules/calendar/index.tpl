{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$today['weekday']}, {$today['month_3Let']} {$today['day_num']}, {$today['year']}</h2>
</div>

{strip}
  {$navlistItems = array()}
  
  {$navlistItems[0] = array()}
  {$navlistItems[0]['title'] = "Today's events"}
  {$navlistItems[0]['url']   = $todaysEventsUrl}
  
  {$navlistItems[1] = array()}
  {$navlistItems[1]['title'] = "Browse events by category"}
  {$navlistItems[1]['url']   = $categorysUrl}
  
  {$navlistItems[2] = array()}
  {$navlistItems[2]['title'] = "Academic calendar"}
  {$navlistItems[2]['url']   = $academicUrl}
{/strip}

{include file="findInclude:common/navlist.tpl" navlistItems=$navlistItems}

{block name="form"}
  <div class="nonfocal">
    <form method="get" action="search.php">
      {include file="findInclude:common/search.tpl" insideForm=true emphasized=false placeholder="Search for events"}
      <fieldset>
        <select id="timeframe" name="timeframe">
          {foreach $searchOptions as $key => $option}
            <option value="{$key}"{if isset($option['selected']) && $option['selected']} selected="selected"{/if} >
              {$option['phrase']}
            </option>
          {/foreach}
        </select>
      </fieldset>	
    </form>
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}
