{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" class="{$section}">
<input id="adminSubmit" type="submit" value="{getLocalizedString type="site" key="BUTTON_SAVE"}" /> 
<h1>{getLocalizedString key="ADMIN_MODULES_HOMESCREEN_TITLE"}</h1>
<p id="moduleDescription" class="preamble">{getLocalizedString key="ADMIN_MODULES_HOMESCREEN_DESCRIPTION"}</p>

<div id="homescreen_layout">
<div class="section_wrapper">
<h2>{getLocalizedString key="ADMIN_MODULES_HOMESCREEN_PRIMARY_TITLE"}</h2>
{include file="findInclude:modules/admin/templates/springboard.tpl" springboardID="primary_modules" springboardItems=$modules.primary section="primary_modules"}
</div>

<div class="section_wrapper">
<h2>{getLocalizedString key="ADMIN_MODULES_HOMESCREEN_SECONDARY_TITLE"}</h2>
{include file="findInclude:modules/admin/templates/springboard.tpl" springboardID="secondary_modules" springboardItems=$modules.secondary section="secondary_modules"}
</div>

<div class="section_wrapper">
<h2>{getLocalizedString key="ADMIN_MODULES_HOMESCREEN_UNUSED_TITLE"}</h2>
{include file="findInclude:modules/admin/templates/springboard.tpl" springboardID="unused_modules" springboardItems=$modules.unused section=""}
</div>
</div><!-- #homescreen layout -->
<div class="springboard_clear"></div>
</form>
{include file="findInclude:modules/admin/templates/footer.tpl"}
