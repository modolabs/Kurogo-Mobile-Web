<h1>Home screen layout</h1>

<div id="homescreen_layout">
<div class="section_wrapper">
<h2>Primary Modules</h2>
{include file="findInclude:common/templates/springboard.tpl" springboardID="primary_modules" springboardItems=$modules.primary}
<div style="clear:left"></div>
</div>

<div class="section_wrapper">
<h2>Secondary Modules</h2>
{include file="findInclude:common/templates/springboard.tpl" springboardID="secondary_modules" springboardItems=$modules.secondary}
<div style="clear:left"></div>
</div>

<div class="section_wrapper">
<h2>Unused Modules</h2>
{include file="findInclude:common/templates/springboard.tpl" springboardID="unused_modules" springboardItems=$modules.unused}
<div style="clear:left"></div>
</div>
</div>