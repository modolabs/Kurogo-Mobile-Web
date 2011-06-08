{$primaryItems = array()}
{$utilityItems = array()}
{$seenSeparator = false}
{foreach $springboardItems as $item}
  {if $item['class'] == 'utility'}
    {$utilityItems[] = $item}
  {else}
    {$primaryItems[] = $item}
  {/if}
{/foreach}

{if count($primaryItems)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$primaryItems}
{/if}

{if count($utilityItems)}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$utilityItems accessKey=false secondary=true}
{/if}
