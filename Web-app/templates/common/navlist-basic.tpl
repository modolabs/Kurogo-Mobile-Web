{extends file="findExtends:common/navlist.tpl"}

{block name="navlistStart"} 
  {if $accessKey|default:true}
    {html_access_key_reset index=count($breadcrumbs)+1}
  {/if}
  <p class="nav{if $secondary} secondary{/if}">
{/block}
    
    {block name="navlistItem"}
      {if isset($item['url'])}
        {if $accessKey|default:true}
          {html_access_key_link href=$item['url'] class=$item['class']|default:null accessKey=false}
            {$itemHTML}
          {/html_access_key_link}
        {else}
          <a href="{$item['url']}" class="{$item['class']|default:''}">
            {$itemHTML}
          </a>
        {/if}
      {else}
        <span class="{$item['class']|default:''}">
          {$itemHTML}
        </span>
      {/if}
      <br/>
    {/block}
    
{block name="navlistEnd"}
  </p>
{/block}
