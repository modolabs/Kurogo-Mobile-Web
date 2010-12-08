{extends file="findExtends:modules/{$moduleID}/times.tpl"}

{block name="mapPane"}
  <p class="image">
    <img src="{$mapImageSrc}" height="{$mapImageSize}" width="{$mapImageSize}" />
  </p>
  {if $timesConfig['serviceLogo']}
    <table align="center">
      <tr>
        <td valign="middle">
          {if $timesConfig['serviceLink'] && !$timesConfig['serviceName']}<a href="{$timesConfig['serviceLink']}">{/if}
            <img src="/modules/{$moduleID}/images/{$timesConfig['serviceLogo']}" />
          {if $timesConfig['serviceLink'] && !$timesConfig['serviceName']}</a>{/if}
        </td>
        {if $timesConfig['serviceName']}
          <td valign="middle">
            {if $timesConfig['serviceLink']}<a href="{$timesConfig['serviceLink']}">{/if}
              {$timesConfig['serviceName']}
            {if $timesConfig['serviceLink']}</a>{/if}
          </td>
        {/if}
      </tr>
    </table>
  {/if}
{/block}

{block name="stopsPane"}
  <span class="smallprint">{$timesConfig['stopTimeHelpText']}</span>
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

