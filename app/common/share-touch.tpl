{if $shareEmailURL}
  <a href="{$shareEmailURL}">Email this article</a>
  {if $shareURL}&nbsp;|&nbsp;{/if}
{/if}
{if $shareURL}
  <a href="http://m.facebook.com/sharer.php?u={$shareURL}&t={$shareRemark}">Facebook</a>
  &nbsp;|&nbsp;
  <a href="http://m.twitter.com/share?url={$shareURL}&amp;text={$shareRemark}&amp;Via=Harvard">Twitter</a>
{/if}
<br />
