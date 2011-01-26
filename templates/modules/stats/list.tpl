<div class="focal">
<h3>{$statItem.title}:</h3>

 <ol>
{foreach $statItem.data as $item}
 <li>
     {if $item.link}
            <a href="{$item.link}">{$item.name}</a>
     {else}
            {$item.name}
     {/if}
     <span class="smallprint">({$item.count|number_format} {$statItem.label})</span> 
 </li>
{/foreach}
 </ol>
</div>