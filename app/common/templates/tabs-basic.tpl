{extends file="findExtends:common/templates/tabs.tpl"}

{block name="tabsStart"}
  <p class="tabs top">
{/block}

{block name="tab"}
  {if $tabKey == $tabbedView['current']}
      <span class="active">{$tabInfo['title']}</span>
  {else}
      <a href="{$tabInfo['url']}">{$tabInfo['title']}</a>
  {/if}
  {if !$isLastTab}&nbsp;|&nbsp;{/if}
{/block}
    
{block name="tabsEnd"}
  </p>
{/block}

{block name="tabBodies"}
  {$tabBodies[$tabbedView['current']]}
{/block}