{strip}

{block name="tabsStart"}
  <ul id="tabs"{if $smallTabs} class="smalltabs"{/if}>
{/block}

    {foreach $tabBodies as $tabKey => $tabBody}
      {if isset($tabbedView['tabs'][$tabKey])}
        {$tabInfo = $tabbedView['tabs'][$tabKey]}
        {$isLastTab = $tabBody@last}
        
        {block name="tab"}
          <li{if $tabKey == $tabbedView['current']} class="active"{/if}>
            <a href="#scrolldown" onclick="showTab('{$tabKey}Tab', this);{$tabInfo['javascript']}">{$tabInfo['title']}</a>
          </li>
        {/block}
        
      {/if}
    {/foreach}
    
{block name="tabsEnd"}
</ul>
{/block}

{strip}
{block name="tabBodies"}
  <div id="tabbodies">
    {foreach $tabBodies as $tabKey => $tabBody}
      {if isset($tabbedView['tabs'][$tabKey])}
        <div class="tabbody" id="{$tabKey}Tab" style="display:none">
          {$tabBody}
        </div>
      {/if}
    {/foreach}
  </div>
  <div class="clear"></div>
{/block}
