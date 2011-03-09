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

{include file="findInclude:common/navlist.tpl" navlistItems=$primaryItems}

{include file="findInclude:common/navlist.tpl" navlistItems=$utilityItems accessKey=false secondary=true}
