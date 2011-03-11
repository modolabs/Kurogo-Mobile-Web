{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h2>{$pageTitle}</h2>
</div>
<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}">
<input type="hidden" name="section" value="{$section}">
<input type="hidden" name="merge" value="0">
{include file="findInclude:modules/admin/templates/feedList.tpl" formListItems=$feeds}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>


{include file="findInclude:common/templates/footer.tpl"}
