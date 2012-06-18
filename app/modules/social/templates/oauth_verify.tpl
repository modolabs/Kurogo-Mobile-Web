{include file="findInclude:common/templates/header.tpl"}
<p class="nonfocal">
{$providerTitle} should have provided you with a code to enter to verify your authorization. Please enter it below
</p>
<div class="focal">
<form method="POST" id="oauthForm" action="{$page}">
<input type="hidden" name="feed" value="{$feed}" />

<p><label for="oauth_verify">Verify Code:</label> <input type="text" name="{$verifierKey}" id="oauth_verify" class="loginFormInput" /> </p>

<div class="formbuttons">
{block name="submit"}<input type="submit" value="Verify" />{/block}
</div>   
</form>

</div>
<p class="nonfocal">
If you do not have a verify code, you need to <a href="{$path}?feed={$feed}&amp;startOver=1">start over</a>
</p>

{include file="findInclude:common/templates/footer.tpl"}
