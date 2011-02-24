<div class="focal">
<h3>{$statItem.title}:</h3>
{if $statsInterval=='day'}
<table class="columns week">
{else}
 <table class="columns twelve">
{/if}
<tr>
{foreach $statItem.days as $day}
     <td><div class="datacol"><div class="colbar" style="height:{$day.percent}%"><div class="collabel">{$day.count}</div></div></div></td>
 {/foreach}
 </tr>
 <tr>
     {if $statsInterval == 'week'}
        <td colspan="6" style="text-align:left; border-left: 1px solid #aaa; padding-left: 4px">{$statItem.days[0].date}</td>
        <td colspan="6" style="text-align:right; border-right: 1px solid #aaa; padding-right: 4px">{$statItem.days[{$statItem.days|count}-1].date}</td>
   {else}{foreach $statItem.days as $day}
        <td>{$day.date}</td>
        {/foreach}
    {/if}
 </tr>
 </table>

</div>