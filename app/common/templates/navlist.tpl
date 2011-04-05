<ul class="nav{if $secondary} secondary{/if}{if $nested} nested{/if}{if $navlistClass} {$navlistClass}{/if}"{if $navlistID} id="{$navlistID}"{/if}>
  {foreach $navlistItems as $item}
    {if !isset($item['separator'])}
      <li{if $item['img']||$item['listclass']} class="{$item['listclass']}{if $item['img']} icon{/if}"{/if}>{include file="findInclude:common/templates/listItem.tpl" subTitleNewline=$subTitleNewline|default:false}</li>
    {/if}
  {/foreach}
</ul>
