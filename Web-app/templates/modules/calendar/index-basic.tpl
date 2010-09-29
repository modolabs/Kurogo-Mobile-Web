{extends file="findExtends:modules/calendar/index.tpl"}

{block name="form"}
  <form method="get" action="search.php">
    <p>
      {include file="findInclude:common/search.tpl" insideForm=true placeholder="Search for events"}
      <br />
      <select id="timeframe" name="timeframe">
        {foreach $searchOptions as $key => $option}
          <option value="{$key}"{if isset($option['selected']) && $option['selected']} selected="selected"{/if} >
            {$option['phrase']}
          </option>
        {/foreach}
      </select>
    </p>
  </form>
{/block}
