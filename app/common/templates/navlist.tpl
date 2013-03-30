{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplateFile=$listItemTemplateFile|default:$defaultTemplateFile}
{if $navListHeading}
<div class="nonfocal listhead">
  <h3>{$navListHeading}</h3>
  {if $navListSubheading}<p class="smallprint">{$navListSubheading}</p>{/if}
</div>
{/if}
<ul class="nav{if $secondary} secondary{/if}{if $nested} nested{/if}{if $navlistClass} {$navlistClass}{/if}"{if $navlistID} id="{$navlistID}"{/if}>
  {foreach $navlistItems as $item}
    {if $hideImages}{$item['img']=null}{/if}
    {if !isset($item['separator'])}
      <li{if $item['img']||$item['listclass']} class="{$item['listclass']}{if $item['img']} icon{/if}"{/if}>{include file="$listItemTemplateFile" subTitleNewline=$subTitleNewline|default:false}</li>
    {/if}
  {/foreach}
</ul>
