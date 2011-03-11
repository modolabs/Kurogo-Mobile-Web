{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h3>You are logged in under the following accounts</h3>
</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$users subTitleNewline=true navlistID="loggedInUsers"}

{include file="findInclude:modules/login/templates/loginForm.tpl"}

{include file="findInclude:common/templates/footer.tpl"}
