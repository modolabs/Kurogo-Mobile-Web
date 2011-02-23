{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>Login</h2>
</div>

{if $loginMessage}
<p class="nonfocal">{$loginMessage}</p>
{/if}

{if $message}
<p class="focal error">{$message}</p>
{/if}

{if $authenticationAuthorities}
<form method="POST" id="loginForm" action="login.php">
<input type="hidden" name="url" value="{$url|escape}">
<p class="focal"><label for="loginUser">{$loginLabel|default:'login'}:</label> <input type="text" name="loginUser" class="loginFormInput"></p>
<p class="focal"><label for="loginUser">{$passwordLabel|default:'password'}:</label> <input type="password" name="loginPassword" class="loginFormInput"></p>
{if $authenticationAuthorities|@count>1}
<p class="focal">
<select name="authority">
{foreach $authenticationAuthorities as $authorityName=>$authorityData}
<option value="{$authorityName}"{if $authority==$authorityName} selected{/if}>{$authorityData.TITLE|escape}</option>
{/foreach}
</select>
</p>
{/if}
{/if}
<p class="focal">
{if $authenticationAuthorities}
    <input type="submit" name="login_submit" id="login_submit" value="Login">
{/if}    
    {foreach $authenticationAuthorityLinks as $authorityName=>$authorityData}
    <a href="{$authorityData.LINK}">{if $authorityData.LOGIN_IMAGE_URL}<img src="{$authorityData.LOGIN_IMAGE_URL}" border="0" class="login_link_image" />{else}{$authorityData.TITLE|escape}{/if}</a>&nbsp;
    {/foreach}
</p>
{if $authenticationAuthorities}
</form>
{/if}
{include file="findInclude:common/footer.tpl"}
