{if $viewlist}
views: <select class="newsinput" onchange="window.location.href=this.value;">
{foreach $viewlist as $view}
<option value="{$view['value']}"{$view['select']}>{$view['title']}</option>
{/foreach}
</select>
{/if}
