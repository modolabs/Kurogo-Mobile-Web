{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


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

{foreach $statsItems as $item}
{$type = $item.type}
{include file="findInclude:modules/stats/$type.tpl" statItem=$item}
{/foreach}

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

{include file="findInclude:common/footer.tpl"}
