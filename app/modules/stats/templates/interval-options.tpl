<form method="get" action="" id="statsoptions" class="nonfocal">
<input type="hidden" name="service" value="{$statsService}" />
<ul id="intervalTabstrip" class="tabstrip {$intervalTabclass}">
{foreach $intervalOptions as $intervalValue=>$intervalData}
<li{if $intervalData.selected} class="active"{/if} interval="{$intervalValue}"><a href="{$intervalData.url}" onclick="return updateIntervalTab('{$intervalValue}')">{$intervalData.title}</a></li>
{/foreach}
</ul>

{block name="statsoptionscustom"}
<div id="statsoptionscustom" class="{$interval}">
<form action="">
<input type="hidden" name="service" value="{$statsService}" />
<input type="hidden" name="interval" value="custom" />
<div><label>{"CUSTOM_FROM"|getLocalizedString}</label>
{html_select_date start_year="- 2" field_array="start" prefix="" time=$starttime}</div>
<div><label>{"CUSTOM_TO"|getLocalizedString}</label>
{html_select_date start_year="- 2" field_array="end" prefix="" time=$endtime}</div>
<input src="/common/images/search_button.png" type="image" />
</div>
{/block}
</form>
