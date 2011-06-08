{if $shareEmailURL || $shareURL}
{if $shareEmailURL}
  <a href="{$shareEmailURL}">Email this article</a>
  {if $shareURL}&nbsp;|&nbsp;{/if}
{/if}
{if $shareURL}
  <a href="http://m.facebook.com/sharer.php?u={$shareURL|escape:'url'}&t={$shareRemark|escape:'url'}">Facebook</a>
  &nbsp;|&nbsp;
  <a href="http://m.twitter.com/share?url={$shareURL|escape:'url'}&text={$shareRemark|escape:'url'}">Twitter</a>
{/if}
<br />
{/if}
