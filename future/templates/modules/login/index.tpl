{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>Login</h2>
</div>

<form method="POST" id="loginForm">
<input type="hidden" name="url" value="{$url|escape}">
<p class="focal"><label for="loginUser">login:</label> <input type="text" name="loginUser" class="loginFormInput"></p>
<p class="focal"><label for="loginUser">password:</label> <input type="password" name="loginPassword" class="loginFormInput"></p>
<p class="focal"><input type="submit" id="login_submit" value="Login"></p>

</form>
{include file="findInclude:common/footer.tpl"}
