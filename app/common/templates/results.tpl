{$defaultTemplateFile="findInclude:common/templates/listItem.tpl"}
{$listItemTemplateFile=$listItemTemplateFile|default:$defaultTemplateFile}
<ul class="results"{if $resultslistID} id="{$resultslistID}"{/if}>
  {foreach $results as $item}
    {if !isset($item['separator'])}
      <li{if $item['img']} class="icon"{/if}>
        {include file="$listItemTemplateFile" subTitleNewline=$subTitleNewline|default:true}
      </li>
    {/if}
  {/foreach}
  {if count($results) == 0}
    {block name="noResults"}
      <li>{"NO_RESULTS"|getLocalizedString}</li>
    {/block}
  {/if}
</ul>
