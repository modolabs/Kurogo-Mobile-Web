{strip}
{block name="formlistStart"}
  <ul class="nav{if $secondary} secondary{/if}"{if $formlistID} id="{$formlistID}"{/if}>
{/block}

    {foreach $formListItems as $item}     
      {if !isset($item['separator'])}
      
        {block name="navlistItem"}
          <li{if $item['img']} class="icon"{/if}>
            {include file="findInclude:common/formListItem.tpl"}
          </li>
        {/block}
      
      {/if}
    {/foreach}

{block name="formlistEnd"}
  </ul>
{/block}

{/strip}
