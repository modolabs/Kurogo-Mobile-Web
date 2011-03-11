{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
<h2>String Configuration</h2>
<p>This section configures the textual strings that appear on the site. 
{if $localFile}<b>Note:</b> This site's strings.ini has local modifications. The administration module does not alter 
the strings-local.ini file. Values shown here are for the strings.ini file{/if}</p>
</div>

<form method="POST">
{include file="findInclude:common/templates/formList.tpl" formListItems=$formListItems}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>
{include file="findInclude:common/templates/footer.tpl"}