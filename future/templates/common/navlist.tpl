{strip}

{block name="navlistStart"}
  <ul class="nav{if $secondary} secondary{/if}{if $nested} nested{/if}"{if $navlistID} id="{$navlistID}"{/if}>
{/block}

    {foreach $navlistItems as $item}     
      {if !isset($item['separator'])}
      
        {block name="navlistItem"}
          <li{if $item['img']} class="icon"{/if}>
            {include file="findInclude:common/listItem.tpl" subTitleNewline=$subTitleNewline|default:false}
          </li>
        {/block}
      
      {/if}
    {/foreach}

{block name="navlistEnd"}
  </ul>
{/block}

{/strip}
