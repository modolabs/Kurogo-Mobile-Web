{if $authenticationAuthorities || $authenticationAuthorityLinks}
<form method="POST" id="loginForm" action="login">
<input type="hidden" name="url" value="{$url|escape}" />
{if $authenticationAuthorities}
<p class="focal"><label for="loginUser">{$moduleStrings.LOGIN_LABEL|default:'login'}:</label> <input type="text" name="loginUser" id="loginUser" class="loginFormInput" /></p>
<p class="focal"><label for="loginPassword">{$moduleStrings.PASSWORD_LABEL|default:'password'}:</label> <input type="password" name="loginPassword" id="loginPassword" class="loginFormInput" /> 
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
    <input type="submit" name="login_submit" id="login_submit" value="Login" /> 
{/if}    
    {if $allowRemainLoggedIn}
    <input type="checkbox" name="remainLoggedIn" value="1" /> Remember me
    {/if}    {if $authenticationAuthorityLinks}<ul>
    {foreach $authenticationAuthorityLinks as $authorityName=>$authorityData}
    <li>{if $authorityData.LOGIN_IMAGE_URL}<input type="image" name="login_link[{$authorityName}]" src="{$authorityData.LOGIN_IMAGE_URL}" />{else}
    <input type="submit" name="login_link[{$authorityName}]" value="{$authorityData.TITLE|escape}" />{/if}</li>
    {/foreach}
    </ul>
    {/if}
</div>
</form>
{/if}