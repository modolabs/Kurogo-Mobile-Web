{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>{$pageTitle}</h2>
</div>
{if $errorMessage}<div class="errorMessage">{$errorMessage|escape}</div>{/if}
<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}">
<input type="hidden" name="section" value="{$section}">
<input type="hidden" name="merge" value="0">
{include file="findInclude:modules/admin/feedList.tpl" feeds=$feeds showDelete=true showNew=true}

<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>


{include file="findInclude:common/footer.tpl"}
