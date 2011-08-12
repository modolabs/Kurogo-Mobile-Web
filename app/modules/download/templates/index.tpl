{include file="findInclude:common/templates/header.tpl"}

{if isset($instructions) && isset($downloadUrl)}
  <p class="nonfocal">
    Add a shortcut to your {$deviceName}'s home screen to get one-click access to {$strings.SITE_NAME}.
  </p>
  
  <div class="focal">
    Instructions: {$instructions}
    {block name="downloadLink"}
      <div class="formbuttons">
        {include file="findInclude:common/templates/formButtonLink.tpl" title="Click here to begin" url=$downloadUrl}
      </div>
    {/block}
  </div>
{else}
  <p class="nonfocal">
    Sorry, we do not have downloads for {$deviceName} devices yet.
  </p>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
