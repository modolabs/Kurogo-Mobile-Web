{if $siteLinksStyle!='none'}
<div id="siteLinks" class="siteLinks">
<div class="siteLinksDescription">{$siteLinksDescription}</div>
{if $siteLinksStyle=='list'}
<ul>
{foreach $siteLinks as $siteItem name="siteLinks"}
<li{if $siteItem.active} class="siteSelected"{/if}><a href="{$siteItem.url}">{$siteItem.title}</a></li>
{/foreach}
</ul>
{elseif $siteLinksStyle=='select'}
<select onchange="redirectToURL(this.options[this.selectedIndex].value)">
{foreach $siteLinks as $siteItem name="siteLinks"}
  <option value="{$siteItem.url|escape}"{if $siteItem.active} selected="true"{/if}>{$siteItem.title}</option>
{/foreach}
</select>
{/if}
</div>
{/if}