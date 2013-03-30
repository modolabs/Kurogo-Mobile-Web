{if $displayType == 'springboard'}
  {block name="homeSpringboard"}
    {include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules['primary'] springboardID="homegrid"}
    {if count($modules['secondary'])}
      {include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules['secondary'] springboardID="homegridSecondary"}
    {/if}
  {/block}
  
{elseif $displayType == 'list'}
  {block name="homeList"}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$modules['primary']}
    {if count($modules['secondary'])}
      {include file="findInclude:common/templates/navlist.tpl" navlistItems=$modules['secondary'] secondary=true accessKeyLink=false}
    {/if}
  {/block}
{/if}
