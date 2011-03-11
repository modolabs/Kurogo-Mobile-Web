{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h2>Page Titles for {$module.id}</h2>

</div>

<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}">
{foreach $pages as $page=>$formListItems}
{include file="findInclude:common/templates/formList.tpl" formListItems=$formListItems}
{/foreach}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>
{include file="findInclude:common/templates/footer.tpl"}