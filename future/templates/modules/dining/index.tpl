{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  {strip}
	<h2>
	  {block name="header"}
  	  Menu for <strong>{$current|date_format:"%a %b %e"}</strong>
  	{/block}
	</h2>
	{/strip}
</div>

{capture name="sideNav" assign="sideNav"}
  {strip}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    {if isset($prev)}
      <a href="{$prev['url']}">
        &lt; {$prev['timestamp']|date_format:"%a %b %e"}
      </a>
    {/if}
    {if isset($prev) && isset( $next)} | {/if}
    {if isset($next)}    
      <a href="{$next['url']}">
        {$next['timestamp']|date_format:"%a %b %e"} &gt;
      </a>
    {/if}
  </div>
  {/strip}
{/capture}

{$sideNav}

{$tabBodies = array()}
{foreach $foodItems as $meal => $foodTypes}
  {capture name="mealHTML" assign="mealHTML"}
    {if count($foodTypes)}
      {foreach $foodTypes as $foodType => $foods}
        {block name="mealPane"}
          <h3>{$foodType}</h3>
          <ul class="nav nested">
            {foreach $foods as $food}
              <li>{$food['item']}</li>
            {/foreach}
          </ul>
        {/block}
      {/foreach}
    {else}
      <p>{$meal|capitalize}</p>
    {/if}
  {/capture}
  {$tabBodies[$meal] = $mealHTML}
{/foreach}

{capture name="locationHTML" assign="locationHTML"}
  {$statusImages = array()}
  
  {$statusImages['open'] = array()}
  {$statusImages['open']['src']   = "dining-status-open"}
  {$statusImages['open']['alt']   = "Open"}
  {$statusImages['open']['title'] = "Open"}
  
  {$statusImages['openrestrictions'] = array()}
  {$statusImages['openrestrictions']['src']   = "dining-status-open-w-restrictions"}
  {$statusImages['openrestrictions']['alt']   = "Open with Restrictions"}
  {$statusImages['openrestrictions']['title'] = "Open w/ restrictions"}
  
  {$statusImages['closed'] = array()}
  {$statusImages['closed']['src']   = "dining-status-closed"}
  {$statusImages['closed']['alt']   = "Open"}
  {$statusImages['closed']['title'] = "Open"}
  
  {$statusImages['closedrestrictions'] = array()}
  {$statusImages['closedrestrictions']['src']   = "dining-status-closed-w-restrictions"}
  {$statusImages['closedrestrictions']['alt']   = "Closed with Upcoming Restrictions"}
  {$statusImages['closedrestrictions']['title'] = "Upcoming restrictions"}

  {block name="locationPane"}
    <div class="columns2">
      {strip}
      {foreach $statusImages as $statusImage}
        <ul class="iconlegend col">
          <li>
            <img src="/modules/{$moduleID}/images/{$statusImage['src']}@2x.png" width="20" height="20" alt="{$statusImage['alt']}"/>
            {$statusImage['title']}
          </li>
        </ul>    
      {/foreach}
      {/strip}
      <div class="clear"></div>
    </div> <!-- class="columns" -->
  
    <p class="fineprint">
      Harvard student ID required. Schedule shown does not account for holidays and other closures.
    </p>
    
    <ul class="nav nested">
      {foreach $diningStatuses as $diningStatus}
        <li class="dininghall {$diningStatus['status']}">
          <a href="{$diningStatus['url']}">
            {$diningStatus['name']}
            <br/>
            <span class="smallprint">{$diningStatus['summary']}</span>
          </a>
        </li>
      {/foreach}
    </ul>
  {/block}
{/capture}
{$tabBodies['location'] = $locationHTML}

{block name="tabView"}
  <a name="scrolldown"> </a>
  <div class="nonfocal">
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{$sideNav}

{include file="findInclude:common/footer.tpl"}
