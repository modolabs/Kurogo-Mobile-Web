{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>{$pageTitle}</h2>
</div>

{if $section}
<form method="POST">
<input type="hidden" name="section" value="{$section}">
{/if}
{include file="findInclude:common/formList.tpl" formListItems=$formListItems}
{if $section}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>
{/if}


{include file="findInclude:common/footer.tpl"}
