{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm">
<input name="submit" id="submit" type="submit" value="Save" />
<h1 id="sectionTitle">{$sectionTitle|default:'Loading...'}</h1>
<ul id="adminFields" class="formFields">

</ul>
</form>
<script type="text/javascript">
    var adminSection = '{$section}';
</script>
{include file="findInclude:modules/admin/templates/footer.tpl"}
