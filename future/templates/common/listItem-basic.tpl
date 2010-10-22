{extends file="findExtends:common/listItem.tpl"}

{block name="itemLink"}
  {if isset($item['url']) && $accessKey|default: true}
    {html_access_key_link href=$item['url'] class=$item['class']|default:null accessKey=false}
      {$item['title']}
    {/html_access_key_link}
    {$subtitleHTML}
    
  {elseif isset($item['url'])}
    {if isset($item['url'])}
      <a href="{$item['url']}" class="{$item['class']|default:''}">
    {/if}
      {$item['title']}
    {if isset($item['url'])}
      </a>
    {/if}
    {$subtitleHTML}
    
  {else}
    <span class="{$item['class']|default:''}">
      {$item['title']}
      {$subtitleHTML}
    </span>
  {/if}
{/block}
