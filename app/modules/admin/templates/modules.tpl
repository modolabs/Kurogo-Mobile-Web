{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm">
<input name="submit" id="adminSubmit" type="submit" value="Save" />
{include file="findInclude:modules/admin/templates/$modulePage.tpl"}
</form>
{include file="findInclude:modules/admin/templates/footer.tpl"}
