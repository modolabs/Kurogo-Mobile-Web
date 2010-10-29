<p class="focal">
<strong>{$statItem.title}:</strong><br />
{foreach $statItem.days as $index => $day}
{if $statsInterval=='day'}
{$day.day}
{/if}
{$day.date}: {$day.count}{if !$day@last}<br/>{/if}
{/foreach}
</p>