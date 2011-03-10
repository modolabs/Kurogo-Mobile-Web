{strip}
{if isset($item['label'])}
    <label>
      {$item['label']}{if $labelColon|default:true}:{/if}
    </label>
{/if}
{if $item.type !='label'}
    <input type="hidden" name="_type[{$item.typename|default:$item.name}]" value="{$item.type}" />
{/if}    
{if isset($item['subtitle'])}
<span class="smallprint">{$item.subtitle|escape}</span>
{/if}
{block name="itemLink"}
    {$item['title']}
    {if $item.type=='text'}
    <input type="text" name="{$item.name}" value="{$item.value|escape}" />
    {elseif $item.type=='paragraph'}
    <textarea name="{$item.name}" cols="28" rows="8">{$item.value|escape}</textarea>
    {elseif $item.type=='boolean'}
    <input type="checkbox" name="{$item.name}" value="1"{if $item.value} checked{/if} />
    {elseif $item.type=='submit'}
    <input type="submit" name="{$item.name}" value="{$item.value|escape}"{if $item.confirm} class="confirm"{/if} />
    {elseif $item.type=='label'}
    {$item.value|escape}
    {elseif $item.type=='radio'}
    {foreach $item['options'] as $_value=>$_label}
    <input type="radio" name="{$item.name}" value="{$_value}"{if $item.value==$_value} checked{/if}> {$_label|escape}&nbsp;
    {/foreach}
    {elseif $item.type=='select'}
    <select name="{$item.name}">
    {if $item['default']}
    <option value="">{$item['default']|escape}</option>
    {/if}
    {foreach $item['options'] as $_value=>$_label}
    <option value="{$_value}"{if $item.value==$_value} selected{/if}>{$_label|escape}</option>
    {/foreach}
    </select>
    {elseif $item.type=='url'}
    <a href="{$item['value']}" class="{$item['class']|default:''}"{if $linkTarget} target="{$linkTarget}"{/if}>{$item['name']}</a>
    {/if}
{/block}
{/strip}