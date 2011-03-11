{if $accessKey|default:true}{html_access_key_reset index=$accessKeyStart}{/if}
<p class="nav{if $secondary} secondary{/if}">
  {foreach $navlistItems as $item}
    {if !isset($item['separator'])}{include file="findInclude:common/templates/listItem.tpl" subTitleNewline=$subTitleNewline|default:false  accessKey=$accessKey|default:true}<br/>{/if}
  {/foreach}
</p>
