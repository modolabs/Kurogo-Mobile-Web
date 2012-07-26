{include file="findInclude:modules/login/templates/header.tpl"}
<p class="nonfocal">
{$authorityTitle} should have provided you with a code to enter to verify your authorization. Please enter it below
</p>
<div class="focal">
<form method="POST" id="loginForm" action="/{$configModule}/login">
<input type="hidden" name="url" value="{$url|escape}" />
<input type="hidden" name="authority" value="{$authority}" />

<p><label for="oauth_verify">Verify Code:</label> <input type="text" name="{$verifierKey}" id="oauth_verify" class="loginFormInput" /> </p>

<div class="formbuttons">
  {include file="findInclude:common/templates/formButtonSubmit.tpl" buttonTitle="Verify"}
</div>   
</form>

</div>
<p class="nonfocal">
If you do not have a verify code, you need to <a href="login?authority={$authority}&amp;startOver=1">start over</a>
</p>

{include file="findInclude:modules/login/templates/footer.tpl"}
