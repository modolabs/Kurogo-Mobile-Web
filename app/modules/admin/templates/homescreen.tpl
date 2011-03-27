<h1>Home screen layout</h1>

<div id="homescreen_layout">
<div class="section_wrapper">
<h2>Primary Modules</h2>
{include file="findInclude:modules/admin/templates/springboard.tpl" springboardID="primary_modules" springboardItems=$modules.primary section="primary_modules"}
</div>

<div class="section_wrapper">
<h2>Secondary Modules</h2>
{include file="findInclude:modules/admin/templates/springboard.tpl" springboardID="secondary_modules" springboardItems=$modules.secondary section="secondary_modules"}
</div>

<div class="section_wrapper">
<h2>Unused Modules</h2>
{include file="findInclude:modules/admin/templates/springboard.tpl" springboardID="unused_modules" springboardItems=$modules.unused section=""}
</div>
</div><!-- #homescreen layout -->
<div class="springboard_clear"></div>