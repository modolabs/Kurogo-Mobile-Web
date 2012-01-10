{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplate=$listItemTemplate|default:$defaultTemplateFile}
{if $navListHeading}
<div class="nonfocal">
  <h3>{$navListHeading}</h3>
</div>
{/if}
{if $accessKey|default:true}{html_access_key_reset index=$accessKeyStart}{/if}
<p class="nav{if $secondary} secondary{/if}">
  {foreach $navlistItems as $item}
    {if !isset($item['separator'])}{include file="$listItemTemplate" subTitleNewline=$subTitleNewline|default:false  accessKey=$accessKey|default:true}<br/>{/if}
  {/foreach}
</p>
