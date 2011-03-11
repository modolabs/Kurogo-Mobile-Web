{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h2>{$pageTitle}</h2>
{if $localFile}<p><b>Note:</b> This site's config.ini has local modifications. The administration module does not alter 
the config-local.ini file. Values shown here are for the config.ini file</p>{/if}
</div>

{if $section}
<form method="POST">
<input type="hidden" name="section" value="{$section}">
{/if}
{include file="findInclude:common/templates/formList.tpl" formListItems=$formListItems}
{if $section}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>
{/if}


{include file="findInclude:common/templates/footer.tpl"}
