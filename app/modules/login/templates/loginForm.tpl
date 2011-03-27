<form method="POST" id="loginForm" action="login">
<div class="focal">
<p>{if $message}{$message|escape}{else}{$moduleStrings.LOGIN_MESSAGE|escape}{/if}</p>
{if $authenticationAuthorities || $authenticationAuthorityLinks}
<input type="hidden" name="url" value="{$url|escape}" />
<input type="hidden" name="startOver" value="1" />
{if $authenticationAuthorities}
<p><label for="loginUser">{$moduleStrings.LOGIN_LABEL|default:'login'}:</label> <input type="text" name="loginUser" id="loginUser" class="loginFormInput" /></p>
<p><label for="loginPassword">{$moduleStrings.PASSWORD_LABEL|default:'password'}:</label> <input type="password" name="loginPassword" id="loginPassword" class="loginFormInput" /> 
<div class="formbuttons"><input type="submit" name="login_submit" id="login_submit" value="Sign in" /></div>   


{if $FORGET_PASSWORD_URL}<p class="smallprint"><a href="{$FORGET_PASSWORD_URL}">{$moduleStrings.FORGET_PASSWORD_TEXT|default:'Forget your password?'|escape}</a></p>{/if}
</p>
{if $authenticationAuthorities|@count>1}
<p>
<select name="authority">
{foreach $authenticationAuthorities as $authorityName=>$authorityData}
<option value="{$authorityName}"{if $authority==$authorityName} selected{/if}>{$authorityData.TITLE|escape}</option>
{/foreach}
</select>
</p>
{/if}
{/if}
<div>
{if $authenticationAuthorities}
{/if}    
{if $authenticationAuthorityLinks}<div>
    {foreach $authenticationAuthorityLinks as $authorityName=>$authorityData}
    <div>{if $authorityData.LOGIN_IMAGE_URL}<input type="image" name="login_link[{$authorityName}]" src="{$authorityData.LOGIN_IMAGE_URL}" />{else}
    <input type="submit" name="login_link[{$authorityName}]" value="{$authorityData.TITLE|escape}" />{/if}</div>
    {/foreach}
    </div>
    {/if}
</div>
{/if}
</div>
{if $allowRemainLoggedIn}
<div class="nonfocal" id="rememberme">
    <input type="checkbox" id="remember" name="remainLoggedIn" /> <label for="remember">Remember me</label>
</div>
{/if}
</form>
