{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="statHeader"}
<h2>{$statsName} Statistics for the Past {$statsDuration} {$statsInterval}s</h2>
{/block}

{block name="statService"}
<p class="secondary">
{foreach $serviceTypes as $service => $title}
{if $service != $statsService}
      <a href="?service={$service}&amp;interval={$statsInterval}">
        {$title} Statistics
      </a>{if !$title@last}<br />{/if}
{/if}
{/foreach}
{/block}