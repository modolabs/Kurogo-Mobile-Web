<form method="POST" id="loginForm" action="/{$configModule}/login">
{foreach $urlArray as $key=>$val}
<input type="hidden" name="{$key}" value="{$val|escape}" />
{/foreach}
<input type="hidden" name="startOver" value="1" />
{if $authority}
{if !$message}
<p class="nonfocal">{$LOGIN_DIRECT_MESSAGE}</p>
{/if}
<input type="hidden" name="authority" value="{$authority}" />
<div class="focal">
    {if $message}<p>{$message}</p>{/if}
    <p><label for="loginUser">{$moduleStrings.LOGIN_LABEL|default:'login'}:</label> <input type="text" name="loginUser" id="loginUser" value="{$loginUser}" class="loginFormInput" /></p>
    <p><label for="loginPassword">{$moduleStrings.PASSWORD_LABEL|default:'password'}:</label> <input type="password" name="loginPassword" id="loginPassword" class="loginFormInput" /> </p>
    {if $allowRemainLoggedIn}<p class="checkitem"><input type="checkbox" id="remember" name="remainLoggedIn"{if $remainLoggedIn} checked{/if} /> <label for="remember">{"REMEMBER_ME"|getLocalizedString}</label></p>{/if}
    {if $allowSaveUsername}<p class="checkitem"><input type="checkbox" id="saveUsername" name="saveUsername"{if $saveUsername} checked{/if} /> <label for="saveUsername">{"SAVE_USERNAME"|getLocalizedString}</label></p>{/if}

    <div class="formbuttons">
      {include file="findInclude:common/templates/formButtonSubmit.tpl" buttonTitle="SIGN_IN"|getLocalizedString}
    </div>   
</div>
{if $FORGET_PASSWORD_URL}<p class="nonfocal smallprint"><a href="{$FORGET_PASSWORD_URL}">{$FORGET_PASSWORD_TEXT}</a></p>{/if}
{else}
{if $authenticationAuthorities.direct}
{if $authenticationAuthorities.indirect}
    <h3 class="nonfocal">{"LOGIN_DIRECTLY"|getLocalizedString}</h3>
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$authenticationAuthorities.direct navlistClass="loginbuttons" navlistID="directList"}


{/if}
{if $authenticationAuthorities.indirect}
{if $authenticationAuthorities.direct}
<p class="nonfocal">{"LOGIN_SEPARATOR"|getLocalizedString}</p>
<h3 class="nonfocal">{"LOGIN_INDIRECT_HEADER"|getLocalizedString}</h3>
{/if}
<p class="smallprint nonfocal">{$LOGIN_INDIRECT_MESSAGE}</p>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$authenticationAuthorities.indirect  navlistClass="loginbuttons" navlistID="indirectList"}
{/if}

{if $allowRemainLoggedIn}
    <p class="nonfocal" id="rememberme"><input type="checkbox" id="remember" name="remainLoggedIn" onclick="toggleRememberMe()" /> <label for="remember">{"REMEMBER_CHOICE"|getLocalizedString}</label></p>
{/if}
{/if}
</form>
