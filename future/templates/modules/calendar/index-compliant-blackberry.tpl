{extends file="findExtends:modules/{$moduleID}/index.tpl"}

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
  {include file="findInclude:common/search-compliant-blackberry.tpl" 
    additionalInputs=$selectSection placeholder="Search for events"}
{/block}
