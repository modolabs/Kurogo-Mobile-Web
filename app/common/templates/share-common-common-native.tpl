{if $shareEmailURL || $shareURL}
<div id="share">
  {capture name="facebookURL" assign="facebookURL"}http://m.facebook.com/sharer.php?u={$shareURL|escape:'url'}&t={$shareRemark|escape:'url'}{/capture}
  {capture name="twitterURL" assign="twitterURL"}http://twitter.com/share?url={$shareURL|escape:'url'}&text={$shareRemark|escape:'url'}{/capture}
  <a onclick="{strip}kgoBridge.shareDialog({ldelim}
    {if $shareEmailURL}
      'mail': '{$shareEmailURL|escape:'javascript'}'{if $shareURL},{/if}
    {/if}
    {if $shareURL}
      'facebook': '{$facebookURL|escape:'javascript'}',
      'twitter': '{$twitterURL|escape:'javascript'}'
    {/if}
  {rdelim});{/strip}"><img src="/common/images/share.png" width="44" height="38" /></a>
</div>
{/if}
