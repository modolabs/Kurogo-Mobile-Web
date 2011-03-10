{extends file="findExtends:common/templates/listItem.tpl"}

{block name="itemLink"}
  {$listItemLabel}
  {if $item['url'] && $accessKey|default:false}
    {html_access_key_link href=$item['url'] class=$item['class']|default:null accessKey=false}
      {$item['title']}
    {/html_access_key_link}
    {$subtitleHTML}
    
  {else}
    {if $item['url']}
      <a href="{$item['url']}" class="{$item['class']|default:''}">
    {/if}
      {$item['title']}
    {if $item['url']}
      </a>
    {/if}
    {if $item['subtitle']}
      <span class="smallprint">
        {if $subTitleNewline|default:true}<br/>{else}&nbsp;{/if}
        {$item['subtitle']}
      </span>
    {/if}
  {/if}
{/block}
