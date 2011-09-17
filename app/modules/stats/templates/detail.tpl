{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:modules/stats/templates/interval-options.tpl"}
<h2 class="nonfocal">{$pageTitle|default:'Detail'}</h2>
{foreach $charts as $chartParams}
{capture assign="chart"}{drawChart chartParams=$chartParams}{/capture}
{if $chart}
<div class="focal">{$chart}</div>
{/if}
{/foreach}
{include file="findInclude:modules/stats/templates/service-options.tpl"}
{include file="findInclude:common/templates/footer.tpl"}
