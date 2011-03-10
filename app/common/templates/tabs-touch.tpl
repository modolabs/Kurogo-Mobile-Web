{extends file="findExtends:common/templates/tabs.tpl"}

{block name="tab"}
  <li{if $tabKey == $tabbedView['current']} class="active"{/if}>
    <a {if $tabKey != $tabbedView['current']}href="{$tabInfo['url']}"{/if}>{$tabInfo['title']}</a>
  </li>
{/block}
    
{block name="tabBodies"}
  <div id="tabbodies">
    <div class="tabbody" id="{$tabKey}Tab">
      {$tabBodies[$tabbedView['current']]}
    </div>
  </div>
{/block}