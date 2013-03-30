{block name="kgoCreditsSite"}
  <div class="copyright">
    {if $strings.COPYRIGHT_LINK}
      <a href="{$strings.COPYRIGHT_LINK}">
    {/if}
        {$strings.COPYRIGHT_NOTICE}
    {if $strings.COPYRIGHT_LINK}
      </a>
    {/if}
  </div>
{/block}

{block name="kgoCreditsKurogo"}
  {$footerKurogo}
{/block}
