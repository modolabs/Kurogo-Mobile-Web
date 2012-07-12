{include file="findInclude:modules/login/templates/header.tpl"}

{if $showMessage}
{include file="findInclude:modules/login/templates/include/message.tpl"}
{if $nativeApp && $redirectURL}
<iframe src="{$redirectURL}" height="0" width=0"></iframe>
{/if}
{else}
{include file="findInclude:modules/login/templates/include/loginForm.tpl"}
{/if}

{include file="findInclude:modules/login/templates/footer.tpl"}
