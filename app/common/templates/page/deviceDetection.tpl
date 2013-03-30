{block name="kgoSiteInformationDeviceDetection"}
  {if $configModule == $homeModuleID && $showDeviceDetection}
    <table class="footertable">
      <tr><th>Pagetype:</th><td>{$pagetype}</td></tr>
      <tr><th>Platform:</th><td>{$platform}</td></tr>
      <tr><th>Browser:</th><td>{$browser}</td></tr>
      <tr><th>User Agent:</th><td>{$smarty.server.HTTP_USER_AGENT}</td></tr>
    </table>
  {/if}
{/block}
