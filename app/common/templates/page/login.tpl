{if $showLogin}
  <div class="loginstatus">
    {block name="loginLink"}
      <ul class="nav secondary loginbuttons">
      <li{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></li>
      </ul>
    {/block}
  </div>
{/if}
