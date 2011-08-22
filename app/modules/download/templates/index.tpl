{include file="findInclude:common/templates/header.tpl"}

{if isset($instructions) && isset($downloadUrl)}
  <p class="nonfocal">
    {$introduction}
  </p>
  
  <div class="focal">
    {$instructions}
    {block name="downloadLink"}
      <div class="formbuttons">
        {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle="DOWNLOAD"|getLocalizedString buttonURL=$downloadUrl}
      </div>
    {/block}
  </div>
{else}
  <p class="nonfocal">
    {"DOWNLOAD_NOT_AVAILABLE"|getLocalizedString:$deviceName}
  </p>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
