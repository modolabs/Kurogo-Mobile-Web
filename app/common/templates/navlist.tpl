{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplateFile=$listItemTemplateFile|default:$defaultTemplateFile}
<ul class="nav{if $secondary} secondary{/if}{if $nested} nested{/if}{if $navlistClass} {$navlistClass}{/if}"{if $navlistID} id="{$navlistID}"{/if}>
  {foreach $navlistItems as $item}
    {if $hideImages}{$item['img']=null}{/if}
    {if !isset($item['separator'])}
      <li{if $item['img']||$item['listclass']} class="{$item['listclass']}{if $item['img']} icon{/if}"{/if}>{include file="$listItemTemplateFile" subTitleNewline=$subTitleNewline|default:false}</li>
    {/if}
  {/foreach}
</ul>
