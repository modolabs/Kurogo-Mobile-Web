{include file="findInclude:common/header.tpl"}


<div class="nonfocal">
	<h2>Menu for <strong>{$current|date_format:"%a %b %e"}</strong></h2>
</div>

{capture name="sideNav" assign="sideNav"}
  {strip}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    {if isset($prev)}
      <a href="{$prev['url']}">
        &lt; {$prev['timestamp']|date_format:"%a %b %e"}
      </a>
    {/if}
    {if isset($prev, $next)} | {/if}
    {if isset($next)}    
      <a href="{$next['url']}">
        {$next['timestamp']|date_format:"%a %b %e"} &gt;
      </a>
    {/if}
  </div>
  {/strip}
{/capture}

{$sideNav}

<a name="scrolldown"> </a>
<div class="nonfocal">
  <ul id="tabs" class="smalltabs">
    {foreach $foodItems as $meal => $foodTypes}
      <li{if $meal == $currentMeal} class="active"{/if}>
        <a href="#scrolldown" onclick="showTab('{$meal}tab', this)">{$meal|capitalize}</a>
      </li>
    {/foreach}
  
    <li><a href="#scrolldown" onclick="showTab('locationstab',this)">Locations</a></li>
  </ul>

  <div id="tabbodies">
    {foreach $foodItems as $meal => $foodTypes}
      <div class="tabbody" id="{$meal}tab" style="display:none">
        {if count($foodTypes)}
          {foreach $foodTypes as $foodType => $foods}
            <h3>{$foodType}</h3>
            <ul class="nav nested">
              {foreach $foods as $food}
                <li>{$food['item']}</li>
              {/foreach}
            </ul>
          {/foreach}
        {else}
          <p>{$meal|capitalize}</p>
        {/if}
      </div>
    {/foreach}
  
    <div class="tabbody" id="locationstab" style="display:none">
      <div class="columns2">
        <ul class="iconlegend col">
          <li>
            <img src="/modules/{$moduleID}/images/dining-status-open@2x.png" width="20" height="20" alt="Open"/>
            Open now
          </li>
          <li>
            <img src="/modules/{$moduleID}/images/dining-status-open-w-restrictions@2x.png" width="20" height="20" alt="Open with Restrictions"/>
            Open w/ restrictions
          </li>
        </ul>
        <ul class="iconlegend col">
          <li>
            <img src="/modules/{$moduleID}/images/dining-status-closed@2x.png" width="20" height="20" alt="Closed"/>
            Closed
          </li>
          <li>
            <img src="/modules/{$moduleID}/images/dining-status-closed-w-restrictions@2x.png" width="20" height="20" alt="Closed with Upcoming Restrictions"/>
            Upcoming restrictions
          </li>
        </ul>
        <div class="clear"></div>
      </div> <!-- class="columns" -->
  
      <p class="fineprint">
        Harvard student ID required. Schedule shown does not account for holidays and other closures.
      </p>
      
      <ul class="nav nested">
        {foreach $diningStatuses as $diningStatus}
          <li class="dininghall {$diningStatus['status']}">
            <a href="{$diningStatus}">
              {$diningStatus['name']}
              <br/>
              <span class="smallprint">{$diningStatus['summary']}</span>
            </a>
          </li>
        {/foreach}
  </ul>
  </div> <!-- id="locationstab" -->
  
  <div class="clear"></div>
  
  </div>
</div>

{$sideNav}

{include file="findInclude:common/footer.tpl"}
