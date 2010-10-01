{capture name="subtitleHTML" assign="subtitleHTML"}
  {if isset($item['subtitle'])}
    {if $subTitleNewline|default:true}
      <br/>
    {else}
      &nbsp;
    {/if}
    <span class="smallprint">{$item['subtitle']}</span>
  {/if}
{/capture}

{if isset($item['label'])}
  {$item['label']}:&nbsp;
{/if}
{block name="itemLink"}
  {if isset($item['url'])}
    <a href="{$item['url']}" class="{$item['class']|default:''}">
  {/if}
    {$item['title']}
    {$subtitleHTML}
  {if isset($item['url'])}
    </a>
  {/if}
{/block}