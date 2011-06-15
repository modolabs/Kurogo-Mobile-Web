{if count($tabBodies) > 1}
  {block name="tabsStart"}
    <ul id="tabs"{if $smallTabs} class="smalltabs"{/if}>
  {/block}
  
      {foreach $tabBodies as $tabKey => $tabBody}
        {if isset($tabbedView['tabs'][$tabKey])}
          {$tabInfo = $tabbedView['tabs'][$tabKey]}
          {$isLastTab = $tabBody@last}
          
          {block name="tab"}
            {if strlen($GOOGLE_ANALYTICS_ID)}
              {$gaArgs = $smarty.get}
              {$gaArgs['_b'] = null}
              {$gaArgs['_path'] = null}
              {$gaLabel = http_build_query($gaArgs, '', '&')}
            {/if}
            <li{if $tabKey == $tabbedView['current']} class="active"{/if}>
              <a href="{block name='tabLink'}#top{/block}" onclick="{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$configModule}', '{$tabKey} tab', '{$gaLabel}']);{/if}showTab('{$tabKey}Tab', this);{$tabInfo['javascript']}">{$tabInfo['title']}</a>
            </li>
          {/block}
          
        {/if}
      {/foreach}
      
  {block name="tabsEnd"}
  </ul>
  {/block}
{/if}

{block name="tabBodies"}
  <div id="tabbodies">
    {foreach $tabBodies as $tabKey => $tabBody}
      {if isset($tabbedView['tabs'][$tabKey])}
        <div class="tabbody" id="{$tabKey}Tab" {if count($tabBodies) > 1}style="display:none"{/if}>
          {$tabBody}
        </div>
      {/if}
    {/foreach}
  </div>
  <div class="clear"></div>
{/block}
