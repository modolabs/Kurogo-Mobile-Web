{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm">
<input name="submit" id="adminSubmit" type="submit" value="Save" />
<h1 id="sectionTitle">&nbsp;</h1>
<p id="sectionDescription" class="preamble">&nbsp;</p>
<ul id="adminFields" class="formfields">

</ul>
</form>
<script type="text/javascript">
    var adminSection = '{$section}';
</script>
{include file="findInclude:modules/admin/templates/footer.tpl"}
