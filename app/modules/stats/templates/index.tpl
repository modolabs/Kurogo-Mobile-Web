{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/stats/templates/include/interval-options.tpl"}
<h2 class="nonfocal">{$pageTitle}</h2>
{include file="findInclude:modules/stats/templates/include/updateStats.tpl"}
{foreach $charts as $chartParams}
<div class="focal">
{drawChart chartParams=$chartParams}
</div>
{/foreach}
{include file="findInclude:modules/stats/templates/include/service-options.tpl"}
{include file="findInclude:common/templates/footer.tpl"}
