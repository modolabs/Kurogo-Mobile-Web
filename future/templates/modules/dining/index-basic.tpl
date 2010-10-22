{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="header"}
  {if $tabbedView['current'] == 'location'}
    {$current|date_format:"%a %B %e"}
  {else}
    Menu for <strong>{$current|date_format:"%a %b %e"}</strong>
  {/if}
{/block}


{block name='sideNavClass'}nonfocal{/block}

{block name="tabView"}
  <div class="focal">
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{block name="mealPane"}
  <h3>{$foodType}</h3>
  <p class="results">
    {foreach $foods as $food}
      {$food['item']}<br/>
    {/foreach}
  </p>
{/block}

{block name="locationPane"}
  {strip}
  <p class="iconlegend"></p>
  {foreach $statusImages as $statusImage}
    <p>
      <img src="/modules/{$moduleID}/images/{$statusImage['src']}.gif" width="13" height="13" alt="{$statusImage['alt']}"/>
      {$statusImage['title']}&nbsp;
    </p>    
  {/foreach}
    
  <p class="fineprint">
    Harvard student ID required. Schedule shown does not account for holidays and other closures.
  </p>
  
  {foreach $diningStatuses as $diningStatus}
    {$statusImage = $statusImages[$diningStatus['status']]}
    <p>
      <img src="/modules/{$moduleID}/images/{$statusImage['src']}.gif" width="13" height="13" alt="{$statusImage['alt']}"/>
      <a class="dininghall {$diningStatus['status']}" href="{$diningStatus['url']}">
        {$diningStatus['name']}
      </a><span class="smallprint">: {$diningStatus['summary']}</span>
    </p>
  {/foreach}
  {/strip}
{/block}
<p>
