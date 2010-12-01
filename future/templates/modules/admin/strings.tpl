{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
<h2>String Configuration</h2>
<p>This section configures the textual strings that appear on the site</p>
</div>

<form method="POST">
{include file="findInclude:common/formList.tpl" formListItems=$formListItems}
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
</form>
{include file="findInclude:common/footer.tpl"}