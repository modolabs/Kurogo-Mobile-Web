<div class="focal">
<h3>{$statItem.title}:</h3>
<table>
{foreach $statItem.data as $name => $percent}
<tr>        
    <td>{$name}:</td>
    <td><div class="datarow"><div class="rowbar" style="width:{$percent}%"></div></div></td>
    <td>{$percent}%</td>
</tr>
{/foreach}
</table>

</div>