<p class="focal">
<strong>{$statItem.title}:</strong><br />
{foreach $statItem.data as $name => $percent}
{$name}: {$percent}%{if !$percent@last}<br/>{/if}
{/foreach}
</p>