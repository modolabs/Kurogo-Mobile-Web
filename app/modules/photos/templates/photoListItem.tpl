{capture name="listItemLabel" assign="listItemLabel"}
  {if isset($item['label'])}
    {if $boldLabels}
      <strong>
    {/if}
      {$item['label']}{if $labelColon|default:true}: {/if}
    {if $boldLabels}
      </strong>
    {/if}
  {/if}
{/capture}
{block name="itemLink"}
  {if $item['url']}
    <a href="{$item['url']}" class="{$item['class']|default:''}"{if $linkTarget || $item['linkTarget']} target="{if $item['linkTarget']}{$item['linkTarget']}{else}{$linkTarget}{/if}"{/if}>
  {/if}
    {if $item['img']}
      <img src="{$item['img']}" alt="{if $item['imgAlt']}{$item['imgAlt']}{/if}"
        {if $item['imgWidth']} width="{$item['imgWidth']}"{/if}
        {if $item['imgHeight']} height="{$item['imgHeight']}"{/if} />
    {/if}
    {$listItemLabel}
    {if $titleTruncate}
      {$item['title']|truncate:$titleTruncate}
    {else}
      {$item['title']}
    {/if}
    {if $item['subtitle']}
      {if $subTitleNewline|default:true}<div{else}&nbsp;<span{/if} class="smallprint">
        {$item['subtitle']}
      {if $subTitleNewline|default:true}</div>{else}</span>{/if}
    {/if}
    {if $item['badge']}
      <span class="badge">{$item['badge']}</span>
    {/if}
    {if $item['type']}
    	<div class="smallprint serviceicon {$item['type']}">{$item['type']}</div>
	{/if}
	{if $item['albumcount']}
    	<div class="smallprint">{$item['albumcount']}</div>
	{/if}
  {if $item['url']}
    </a>
  {/if}
{/block}
