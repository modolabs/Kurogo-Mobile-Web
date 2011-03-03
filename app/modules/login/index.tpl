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

{if $authenticationAuthorities}
<form method="POST" id="loginForm" action="login.php">
<input type="hidden" name="url" value="{$url|escape}">
<p class="focal"><label for="loginUser">{$moduleStrings.LOGIN_LABEL|default:'login'}:</label> <input type="text" name="loginUser" class="loginFormInput"></p>
<p class="focal"><label for="loginUser">{$moduleStrings.PASSWORD_LABEL|default:'password'}:</label> <input type="password" name="loginPassword" class="loginFormInput"> 
{if $FORGET_PASSWORD_URL}<span><a href="{$FORGET_PASSWORD_URL}">{$moduleStrings.FORGET_PASSWORD_TEXT|default:'Forget your password?'|escape}</a></span>{/if}
</p>
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
<div class="focal">
{if $authenticationAuthorities}
    <input type="submit" name="login_submit" id="login_submit" value="Login">
{/if}    
    {if $authenticationAuthorityLinks}<ul>
    {foreach $authenticationAuthorityLinks as $authorityName=>$authorityData}
    <li><a href="{$authorityData.LINK}">{if $authorityData.LOGIN_IMAGE_URL}<img src="{$authorityData.LOGIN_IMAGE_URL}" border="0" class="login_link_image" />{else}{$authorityData.TITLE|escape}{/if}</a></li>
    {/foreach}
    </ul>
    {/if}
</div>
{if $authenticationAuthorities}
</form>
{/if}
{include file="findInclude:common/footer.tpl"}
