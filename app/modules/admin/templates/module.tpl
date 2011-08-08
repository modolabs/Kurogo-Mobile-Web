{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" class="{$section}">
<input id="adminSubmit" type="submit" value="{getLocalizedString key="BUTTON_SAVE"}" />
<h1 id="moduleTitle"><img src="/modules/home/images/compliant/{$moduleID}.png" width="50" height="50" alt="{$moduleName|escape}" id="moduleImage" /> {$moduleName}</h1>
<ul id="adminSections"></ul>
<p id="moduleDescription" class="preamble">&nbsp;</p>
<ul id="adminFields" class="formfields">

</ul>
<script type="text/javascript">
    var moduleID = '{$moduleID}';
    var adminSection = '{$moduleSection}';
</script>
</form>
{include file="findInclude:modules/admin/templates/footer.tpl"}
