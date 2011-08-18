{include file="findInclude:common/templates/header.tpl"}

{if $message}
<p class="nonfocal error">{$message|escape}</p>
{/if}

<p class="nonfocal">{$LOGIN_INDEX_MESSAGE}</p>

{include file="findInclude:modules/login/templates/loginForm.tpl"}

{include file="findInclude:common/templates/footer.tpl"}
