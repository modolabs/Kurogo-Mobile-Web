<p class="focal">
<strong>{$statItem.title}:</strong><br />

{foreach $statItem.data as $index => $item}
{$index+1}. 
{if $item.link}
<a href="{$item.link}">{$item.name}</a> 
{else}
{$item.name}
{/if}
<span class="smallprint">({$item.count|number_format} {$statItem.label})</span>{if !$item@last}<br/>{/if}
{/foreach}
</p>