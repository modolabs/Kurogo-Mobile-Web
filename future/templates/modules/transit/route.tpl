{include file="findInclude:common/header.tpl"}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
  {strip}
  {block name="mapPane"}
    <div id="map">
      <img src="{$mapImageSrc}" height="{$mapImageSize}" width="{$mapImageSize}" />
    </div>
  {/block}
  {strip}
{/capture}
{$tabBodies['map'] = $mapPane}

{capture name="stopsPane" assign="stopsPane"}
  {strip}
  {block name="stopsPane"}
    <span class="smallprint">{$routeConfig['stopTimeHelpText']}</span>
    <div id="schedule">
      {include file="findInclude:common/results.tpl" results=$routeInfo['stops']}
    </div>
  {/block}
  {strip}
{/capture}
{$tabBodies['stops'] = $stopsPane}

<a name="scrolldown"></a>		
<div class="focal shaded">
  <h2 class="refreshContainer">
    {block name="refreshButton"}
      <div id="refresh"><a href="{$refreshURL}">
        <img src="/common/images/refresh.png" alt="Update" width="82" height="32">
      </a></div>
    {/block}
    {$routeInfo['name']}
  </h2>
  
  <p class="smallprint logoContainer clear">
    {block name="routeInfo"}
      {if $routeInfo['description']}
        {$routeInfo['description']}<br/>
      {/if}
      {if $routeInfo['summary']}
        {$routeInfo['summary']}<br/>
      {/if}
      {if $routeInfo['running']}
        Refreshed at {$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span>
        {if $routeConfig['serviceName']} using {$routeConfig['serviceName']}{/if}
      {else}
        Bus not running.
      {/if}
    {/block}
    
    {block name="headerServiceLogo"}
      {if $routeConfig['serviceLogo']}
        <span id="servicelogo">
          {if $routeConfig['serviceLink']}<a href="{$routeConfig['serviceLink']}">{/if}
            <img src="/modules/{$moduleID}/images/{$routeConfig['serviceLogo']}" />
          {if $routeConfig['serviceLink']}</a>{/if}
        </span>
      {/if}
    {/block}
  </p>
{block name="tabView"}
	  {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
{/block}
</div>

{include file="findInclude:common/footer.tpl"}
