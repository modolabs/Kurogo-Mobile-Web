{if $shareEmailURL || $shareURL}
{$shareTitle|default:{"SHARE_THIS_ITEM"|getLocalizedString}}:&nbsp;
  {if $shareEmailURL}
    <a href="{$shareEmailURL}">{"SHARE_OPTION_EMAIL"|getLocalizedString}</a>
  {/if}
  {if $shareURL}
    &nbsp;|&nbsp;
    <a href="http://m.facebook.com/sharer.php?u={$shareURL|escape:'url'}&t={$shareRemark|escape:'url'}">{"SHARE_OPTION_FACEBOOK"|getLocalizedString}</a>
    &nbsp;|&nbsp;
    <a href="http://twitter.com/share?url={$shareURL|escape:'url'}&text={$shareRemark|escape:'url'}">{"SHARE_OPTION_TWITTER"|getLocalizedString}</a>
  {/if}
  <br />
{/if}
