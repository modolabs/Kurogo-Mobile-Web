{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" enctype="multipart/form-data">
<input type="hidden" name="type" value="site" />
<input type="hidden" name="section" id="section" value="{$section}" />
<input type="hidden" name="subsection" id="subsection" value="{$subsection} /">
<input name="submit" id="adminSubmit" type="submit" value="{"BUTTON_SAVE"|getLocalizedString}" />
<h1 id="sectionTitle">&nbsp;</h1>
<ul id="adminSections"></ul>
<p id="sectionDescription" class="preamble">&nbsp;</p>
<div id="adminFields" class="formfields">

</div>
</form>
<script type="text/javascript">
    var adminSection = '{$section}';
    var adminSubsection = '{$subsection}';
</script>
{include file="findInclude:modules/admin/templates/footer.tpl"}
