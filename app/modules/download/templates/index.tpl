{include file="findInclude:common/templates/header.tpl"}

{if isset($instructions) && isset($downloadUrl)}
  <p class="nonfocal">
    Add a shortcut to your {$deviceName}'s home screen to get one-click access to {$strings.SITE_NAME}.
  </p>
  
  <div class="focal">
    Instructions: {$instructions}
    {block name="downloadLink"}
      <div class="formbuttons">
        <a class="formbutton" href="{$downloadUrl}"><div>Click here to begin</div></a>
      </div>
    {/block}
  </div>
{else}
  <p class="nonfocal">
    Sorry, we do not have downloads for {$deviceName} devices yet.
  </p>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
