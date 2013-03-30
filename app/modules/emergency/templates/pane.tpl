{extends file="findExtends:common/templates/pane.tpl"}

{block name="content"}
  <div class="emergency-module-link-wrapper">{* ellipsizer needs relatively positioned container *}
    {$emergencyPaneInstanceId = "{$configModule}PaneInstanceId"}
    <a id="{$emergencyPaneInstanceId}" class="emergency-module-link" href="{$emergencyModuleURL}">
      <div class="emergency-notice-wrapper">
        {if $hasNotice}
            <div class="emergency-notice{if $emergencyNotice@first} emergency-featured{/if}">
              <div class="title">{$title}</div>
              <div class="pubdate">{$date|date_format:$dateFormat} {$date|date_format:$timeFormat}</div>
              <div class="content">{$text|sanitize_html:"inline|block|list"}</div>
            </div>
        {else}
          {$moduleStrings.NO_EMERGENCY}
        {/if}
      </div>
    </a>
  </div>
  <script type="text/javascript">
    var {$configModule}PaneEllipsizer = new ellipsizer();
    var elem = document.getElementById('{$emergencyPaneInstanceId}');
    if (elem) {$configModule}PaneEllipsizer.addElement(elem);
  </script>
{/block}
