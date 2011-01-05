{extends file="findExtends:common/navlist.tpl"}


{block name="navlistStart"} 
  {if $accessKey|default:true}
    {html_access_key_reset index=$accessKeyStart}
  {/if}
  <p class="nav{if $secondary} secondary{/if}">
{/block}
    
    {block name="navlistItem"}
      {include file="findInclude:common/listItem.tpl" subTitleNewline=$subTitleNewline|default:false}
      {if !$lastListItem}<br/>{/if}
    {/block}
    
{block name="navlistEnd"}
  </p>
{/block}
