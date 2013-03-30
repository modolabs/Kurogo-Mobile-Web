{if count($tabBodies) > 1}
  {block name="tabsStart"}
    <ul id="tabs"{if $smallTabs} class="smalltabs"{/if}>
  {/block}
      {foreach $tabbedView['tabs'] as $tabKey => $tabInfo}
        {if isset($tabbedView['tabs'][$tabKey])}
          {$isLastTab = $tabInfo@last}
          
          {block name="tab"}
            {if strlen($GOOGLE_ANALYTICS_ID)}
              {$gaArgs = $smarty.get}
              {$gaArgs['_b'] = null}
              {$gaArgs['_path'] = null}
              {$gaLabel = http_build_query($gaArgs, '', '&')}
            {/if}
            <li id="{$tabInfo['id']}-tab" {if $tabKey == $tabbedView['current']} class="active"{/if}>
              <a href="{block name='tabLink'}#top{/block}" onclick="(function(){ var tabKey = '{$tabKey}';var tabId = '{$tabInfo['id']}';var tabCookie = '{$tabbedView['tabCookie']}';{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$configModule}', '{$tabKey} tab', '{$gaLabel}']);{/if}showTab(tabId);setCookie(tabCookie, tabKey, 0, '{$smarty.const.COOKIE_PATH}');{$tabInfo['javascript']} })();">{$tabInfo['title']}</a>
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
    {foreach $tabbedView['tabs'] as $tabKey => $tabInfo}
      {if isset($tabbedView['tabs'][$tabKey])}
        <div class="tabbody {$tabKey}-tabbody" id="{$tabInfo['id']}-tabbody" {if count($tabBodies) > 1}style="display:none"{/if}>
          {$tabBodies[$tabKey]}
        </div>
      {/if}
    {/foreach}
  </div>
  <div class="clear"></div>
{/block}
