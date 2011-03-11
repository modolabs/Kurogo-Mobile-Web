{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h2>{$pageTitle}</h2>
</div>
<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}">
{if $section}
<input type="hidden" name="section" value="{$section}">
{/if}
{include file="findInclude:common/templates/formList.tpl" formListItems=$formListItems}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>


{include file="findInclude:common/templates/footer.tpl"}
