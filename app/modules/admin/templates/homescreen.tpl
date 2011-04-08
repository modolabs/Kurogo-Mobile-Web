<h1>Home Screen Layout</h1>
<p id="moduleDescription" class="preamble">Use this section to manage the modules that appear on the
home screen. Primary modules appear larger and can be hidden by users using the Customize module. Secondary
modules appear smaller and cannot be removed or rearranged by users. You can also update the label of
each module as it appears on the home screen. To remove a module, drag it to the unused modules section.</p>

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