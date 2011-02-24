<ul class="nav{if $secondary} secondary{/if}{if $nested} nested{/if}"{if $navlistID} id="{$navlistID}"{/if}>
  {foreach $navlistItems as $item}
    {if !isset($item['separator'])}
      <li{if $item['img']} class="icon"{/if}>{include file="findInclude:common/listItem.tpl" subTitleNewline=$subTitleNewline|default:false}</li>
    {/if}
  {/foreach}
</ul>
