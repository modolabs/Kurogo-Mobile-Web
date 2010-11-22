{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>{$pageTitle}</h2>
</div>
<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}">
{include file="findInclude:common/formList.tpl" formListItems=$formListItems}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>


{include file="findInclude:common/footer.tpl"}
