<form method="get" action="" id="statsoptions" class="nonfocal">
<input type="hidden" name="interval" value="{$interval}" />
<ul id="serviceTabstrip" class="tabstrip {$serviceTabclass}">
{foreach $serviceOptions as $serviceValue=>$serviceData}
<li{if $serviceData.selected} class="active"{/if}><a href="{$serviceData.url}">{$serviceData.title}</a></li>
{/foreach}
</ul>
</form>
