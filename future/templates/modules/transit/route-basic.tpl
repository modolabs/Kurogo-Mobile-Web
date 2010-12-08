{extends file="findExtends:modules/{$moduleID}/route.tpl"}

{block name="mapPane"}
  <p class="image">
    <img src="{$mapImageSrc}" height="{$mapImageSize}" width="{$mapImageSize}" />
  </p>
  {if $routeConfig['serviceLogo']}
    <table align="center">
      <tr>
        <td valign="middle">
          {if $routeConfig['serviceLink'] && !$routeConfig['serviceName']}<a href="{$routeConfig['serviceLink']}">{/if}
            <img src="/modules/{$moduleID}/images/{$routeConfig['serviceLogo']}" />
          {if $routeConfig['serviceLink'] && !$routeConfig['serviceName']}</a>{/if}
        </td>
        {if $routeConfig['serviceName']}
          <td valign="middle">
            {if $routeConfig['serviceLink']}<a href="{$routeConfig['serviceLink']}">{/if}
              {$routeConfig['serviceName']}
            {if $routeConfig['serviceLink']}</a>{/if}
          </td>
        {/if}
      </tr>
    </table>
  {/if}
{/block}

{block name="stopsPane"}
  <span class="smallprint">{$routeConfig['stopTimeHelpText']}</span>
  <table width="100%" id="schedule">
    {foreach $routeInfo['stops'] as $routeID => $stop}
      <tr>
        <td width="18px" valign="middle">
          <img src="{$stop['img']}" width="16" height="13" alt="Bus arriving next at this stop" />
        </td>
        <td valign="middle"{if $stop['upcoming']} class="current"{/if}>
          <a href="{$stop['url']}">
            {$stop['title']}
          </a>
        </td>
      </tr>
    {/foreach}
  </table>
{/block}

{block name="refreshButton"}
{/block}

{block name="headerServiceLogo"}
{/block}

{block name="routeInfo"}
  {$smarty.block.parent}
  (<a href="{$refreshURL}">refresh</a>)
{/block}

{block name="tabView"}
    {$tabBodies['stops']}
    {$tabBodies['map']}
{/block}

