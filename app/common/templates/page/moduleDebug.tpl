{block name="kgoSiteInformationModuleDebug"}
  {if $moduleDebug && count($moduleDebugStrings)}
    <table class="footertable">
    {foreach $moduleDebugStrings as $key=>$value}
      <tr><th>{$key|escape}:</th><td>{$value|escape}</td></tr>
    {/foreach}
    </table>
  {/if}
{/block}
