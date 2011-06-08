{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
{block name="statHeader"}
<h2>Site statistics for the past:</h2>
<ul id="tabstrip" class="fourtabs">
{foreach $statclasses as $stclass}
<li{$stclass.active}><a href="?service={$statsService}&amp;interval={$stclass.interval}">{$stclass.title}</a></li>
{/foreach}
</ul>
{/block}
</div>

{if $statsItems.total}
{include file="findInclude:modules/stats/templates/total.tpl" statItem=$statsItems.total}
{/if}
{if $statsItems.trend}
{include file="findInclude:modules/stats/templates/trend.tpl" statItem=$statsItems.trend}
{/if}
{if $statsItems.bar_percentage}
{include file="findInclude:modules/stats/templates/bar_percentage.tpl" statItem=$statsItems.bar_percentage}
{/if}
{if $statsItems.total}
{include file="findInclude:modules/stats/templates/list.tpl" statItem=$statsItems.list}
{/if}

{block name="statService"}
<ul class="secondary">
{foreach $serviceTypes as $service => $title}
{if $service != $statsService}
    <li>
      <a href="?service={$service}&amp;interval={$statsInterval}">
        {$title} Statistics
      </a>
    </li>
{/if}
{/foreach}
</ul>
{/block}

{include file="findInclude:common/templates/footer.tpl"}
