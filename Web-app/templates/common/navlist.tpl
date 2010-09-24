{strip}

{block name="navlistStart"}<ul class="nav{if $secondary} secondary{/if}">{/block}
  {foreach $navlistItems as $index => $item}
    {capture name="itemHTML" assign="itemHTML"}
      {$item['title']}
      {if isset($item['subtitle'])}
        &nbsp;<span class="smallprint">{$item['subtitle']}</span>
      {/if}
    {/capture}
    {block name="navlistItem"}
      <li>
        {if isset($item['url'])}
          <a href="{$item['url']}" class="{$item['class']|default:''}">
        {/if}
            {$itemHTML}
        {if isset($item['url'])}
          </a>
        {/if}
      </li>
    {/block}
  {/foreach}
{block name="navlistEnd"}</ul>{/block}

{/strip}
