{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>Login</h2>
</div>

{if $moduleStrings.LOGIN_MESSAGE}
<p class="nonfocal">{$moduleStrings.LOGIN_MESSAGE|escape}</p>
{/if}

{if $message}
<p class="focal error">{$message}</p>
{/if}

{include file="findInclude:modules/login/loginForm.tpl"}

{include file="findInclude:common/footer.tpl"}
