{extends file="findExtends:common/templates/page/head.tpl"}

{block name="kgoHeadPageTitle"}
  <title>{$pageTitle|strip_tags|escape:'htmlall'}</title>
{/block}

{block name="kgoHeadJavascriptURLBase"}
  {* will be called by onAjaxLoad() *}
  <script type="text/javascript">
      var KUROGO_PAGETYPE='{$pagetype}';
      var KUROGO_PLATFORM='{$platform}';
      var KUROGO_BROWSER='{$browser}';
  </script>
{/block}

{block name="kgoHeadJavascriptAnalytics"}
  {if strlen($GOOGLE_ANALYTICS_ID)}
    <script type="text/javascript">
      var _gaq = _gaq || []; {* suppress event tracking errors *}
    </script>
  {/if}
{/block}

{block name="kgoHeadJavascriptBlocks"}
  {* will be called by onAjaxLoad() *}
{/block}

{block name="kgoHeadJavascriptOnLoadBlocks"}
  {* will be called by onAjaxLoad() *}
{/block}

{block name="kgoHeadCSS"}
  <style type="text/css">
  * { -webkit-touch-callout: none; }
  </style>
  {$smarty.block.parent}
{/block}

{block name="kgoHeadJavascript"}
  <script type="text/javascript">
    // Native apps replace this with js which initializes per-OS/version functionality
    {$webBridgeConfig['jsInit']}
  </script>
  {$smarty.block.parent}
  <script type="text/javascript">
      var kgoWebBridgeConfig = {$webBridgeConfig['staticConfig']};
      {if $webBridgeJSLocalizedStrings}kgoWebBridgeConfig['localizedStrings'] = {$webBridgeJSLocalizedStrings};{/if} 
      
      // overrides are used so native app variable declarations only exist in one
      // place in the server code and other js variables can be changed.
      // This is done as a dictionary because older versions of apps may not set newer values.
      var configMappings = {$webBridgeConfig['configMappings']};
      
      var bridgeConfig = {$webBridgeConfig['bridgeConfig']};
      for (var key in configMappings) {
          if (key in bridgeConfig) {
              kgoWebBridgeConfig[configMappings[key]] = bridgeConfig[key];
          }
      }

      var kgoBridge = new kgoBridgeHandler(kgoWebBridgeConfig);
  </script>
{/block}

{block name="kgoHeadViewportTag"}
  {$scalable = false}
  {$smarty.block.parent}
{/block}

{block name="kgoHeadHomeScreenIcon"}{/block}
