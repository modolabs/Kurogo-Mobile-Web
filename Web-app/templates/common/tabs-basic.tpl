<p class="tabs top">
  {strip}
  {foreach $tabbedView['tabs'] as $tab}
    {if $tab == $tabbedView['current']}
      <span class="active">{$tab|capitalize}</span>
    {else}
      <a href="{$tabbedView['url']}&tab={$tab}">{$tab|capitalize}</a>
    {/if}
    {if !$tab@last} | {/if}
  {/foreach}
  {/strip}
</p>
{$tabBodies[$tabbedView['current']]}
