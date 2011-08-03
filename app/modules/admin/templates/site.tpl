{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm">
<input type="hidden" name="section" id="section" value="{$section}" />
<input type="hidden" name="subsection" id="subsection" value="{$subsection} /">
<input name="submit" id="adminSubmit" type="submit" value="{getLocalizedString key="BUTTON_SAVE" type="site"}" />
<h1 id="sectionTitle">&nbsp;</h1>
<ul id="adminSections"></ul>
<p id="sectionDescription" class="preamble">&nbsp;</p>
<ul id="adminFields" class="formfields">

</ul>
</form>
<script type="text/javascript">
    var adminSection = '{$section}';
    var adminSubsection = '{$subsection}';
</script>
{include file="findInclude:modules/admin/templates/footer.tpl"}
