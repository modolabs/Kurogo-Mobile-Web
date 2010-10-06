<ul id="tabs" class="smalltabs">
  {foreach $tabbedView['tabs'] as $tab}
    {$tabJavascript=''}
    {if isset($tabbedView['javascript'][$tab])}
      {$tabJavascript=$tabbedView['javascript'][$tab]}
    {/if}
    <li{if $tab == $tabbedView['current']} class="active"{/if}>
      <a href="#scrolldown" onclick="showTab('{$tab|replace:' ':'_'}Tab', this);{$tabJavascript}">{$tab|capitalize}</a>
    </li>
  {/foreach}
</ul>

<div id="tabbodies">
  {foreach $tabbedView['tabs'] as $tab}
    <div class="tabbody" id="{$tab|replace:' ':'_'}Tab" style="display:none">
      {$tabBodies[$tab]}
    </div>
  {/foreach}
</div>
<div class="clear"></div>
