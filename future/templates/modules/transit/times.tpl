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
    <span class="smallprint">Bus arrives next at the highlighted stops</span>
    <table cellpadding="0" cellspacing="0" border="0" id="schedule">
      {foreach $routeInfo['stops'] as $index => $stop}
        <tr><td{if $stop['upcoming']} class="current"{/if}>
          <a href="{$stop['url']}" id="stop_($index}"><span class="sid"> </span> {$stop['name']}</a>
        </td></tr>
      {/foreach}
    </table>
  {/block}
  {strip}
{/capture}
{$tabBodies['stops'] = $stopsPane}

{block name="tabView"}
	<a name="scrolldown"></a>		
  <div class="focal shaded">
		<h2 class="refreshContainer">
		  <div id="refresh"><a href="{$refreshURL}">
        <img src="/common/images/refresh.png" alt="Update" width="82" height="32">
		  </a></div>
		  {$routeInfo['name']}
	  </h2>
		
    <p class="smallprint logoContainer clear">
      {if $routeInfo['description']}
        {$routeInfo['description']}<br/>
      {/if}
      {if $routeInfo['summary']}
        {$routeInfo['summary']}<br/>
      {/if}
      {if $routeInfo['running']}
        Refreshed at {$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span>
        {if $timesConfig['serviceName']} using {$timesConfig['serviceName']}{/if}
      {else}
        Bus not running.
      {/if}
      
      {if $timesConfig['serviceLogo']}
        <span id="servicelogo">
          {if $timesConfig['serviceLink']}<a href="{$timesConfig['serviceLink']}">{/if}
            <img src="/modules/{$moduleID}/images/{$timesConfig['serviceLogo']}" />
          {if $timesConfig['serviceLink']}</a>{/if}
        </span>
      {/if}
    </p>
	  {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
	</div>
{/block}

{include file="findInclude:common/footer.tpl"}
