{strip}
{if isset($item['label'])}
    <label>
      {$item['label']}{if $labelColon|default:true}:{/if}
    </label>
{/if}
{block name="itemLink"}
    {$item['title']}
    {if $item.type=='text'}
    <input type="text" name="{$item.name}" value="{$item.value|escape}">
    {elseif $item.type=='boolean'}
    <input type="checkbox" name="{$item.name}" value="1"{if $item.value} checked{/if}>
    {elseif $item.type=='submit'}
    <input type="submit" name="{$item.name}" value="{$item.value|escape}">
    {elseif $item.type=='label'}
    {$item.value|escape}
    {/if}
{/block}
{/strip}