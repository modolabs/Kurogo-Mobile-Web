{extends file="findExtends:common/templates/page/login.tpl"}

{block name="loginLink"}
  <p{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></p>
{/block}
