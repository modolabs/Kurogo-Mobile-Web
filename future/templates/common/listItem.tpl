{strip}
{capture name="subtitleHTML" assign="subtitleHTML"}
  {if isset($item['subtitle'])}
    {if $subTitleNewline|default:true}<br/>{else}&nbsp;{/if}
    <span class="smallprint">{$item['subtitle']}</span>
  {/if}
{/capture}

{if isset($item['label'])}
  {if $boldLabels}
    <strong>
  {/if}
      {$item['label']}{if $labelColon|default:true}:&nbsp;{/if}
  {if $boldLabels}
    </strong>
  {/if}
{/if}
{block name="itemLink"}
  {if isset($item['url'])}
    <a href="{$item['url']}" class="{$item['class']|default:''}"{if $linkTarget} target="{$linkTarget}"{/if}>
  {/if}
    {if isset($item['img'])}
      <img src="{$item['img']}" alt="{$item['title']}"{if $item['imgWidth']} 
        width="{$item['imgWidth']}"{/if}{if $item['imgHeight']} 
        height="{$item['imgHeight']}"{/if}{if $item['imgAlt']} 
        alt="{$item['imgAlt']}"{/if} />
    {/if}
    {$item['title']}
    {$subtitleHTML}
    {if isset($item['badge'])}
      <span class="badge">{$item['badge']}</span>
    {/if}
  {if isset($item['url'])}
    </a>
  {/if}
{/block}
{/strip}