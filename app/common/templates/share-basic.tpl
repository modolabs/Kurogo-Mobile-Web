{if $shareEmailURL || $shareURL}
{$shareTitle|default:'Share this item'}:&nbsp;
  {if $shareEmailURL}
    <a href="{$shareEmailURL}">Email</a>
  {/if}
  {if $shareURL}
    &nbsp;|&nbsp;
    <a href="http://m.facebook.com/sharer.php?u={$shareURL|escape:'url'}&t={$shareRemark|escape:'url'}">Facebook</a>
    &nbsp;|&nbsp;
    <a href="http://twitter.com/share?url={$shareURL|escape:'url'}&text={$shareRemark|escape:'url'}">Twitter</a>
  {/if}
  <br />
{/if}
