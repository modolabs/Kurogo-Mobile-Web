{extends file="findExtends:common/templates/page/login.tpl"}

{block name="loginLink"}
  <div{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></div>
{/block}
