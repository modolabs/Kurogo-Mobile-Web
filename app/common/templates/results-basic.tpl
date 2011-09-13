{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplateFile=$listItemTemplateFile|default:$defaultTemplateFile}
{if $accessKey|default:false}
  {html_access_key_reset index=$accessKeyStart}
{/if}
<div class="focal">
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <p>{include file="$listItemTemplateFile" accessKey=$accessKey|default:false}</p>
    {/if}
  {/foreach}    
  {if count($results) == 0}
    <span>{"NO_RESULTS"|getLocalizedString}</span>
  {/if}
</div>
