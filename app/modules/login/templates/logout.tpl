{include file="findInclude:modules/login/templates/header.tpl"}

{include file="findInclude:modules/login/templates/include/message.tpl"}
{if $nativeApp && $redirectURL}
<iframe src="{$redirectURL}" height="0" width=0"></iframe>
{/if}

{include file="findInclude:modules/login/templates/footer.tpl"}
