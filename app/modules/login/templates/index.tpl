{include file="findInclude:modules/login/templates/header.tpl"}

{if $message}
<p class="nonfocal error">{$message|escape}</p>
{/if}

<p class="nonfocal">{$LOGIN_INDEX_MESSAGE}</p>

{include file="findInclude:modules/login/templates/include/loginForm.tpl"}

{include file="findInclude:modules/login/templates/footer.tpl"}
