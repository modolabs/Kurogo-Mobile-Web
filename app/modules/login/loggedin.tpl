{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h3>You are logged in under the following accounts</h3>
</div>

{include file="findInclude:common/navlist.tpl" navlistItems=$users subTitleNewline=true navlistID="loggedInUsers"}

{include file="findInclude:modules/login/loginForm.tpl"}

{include file="findInclude:common/footer.tpl"}
