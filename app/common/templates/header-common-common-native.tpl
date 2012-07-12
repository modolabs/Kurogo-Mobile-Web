{extends file="findExtends:common/templates/header.tpl"}

{block name="pageTitle"}{$pageTitle|strip_tags|escape:'htmlall'}{/block}

{block name="urlBaseJavascript"}
  <script type="text/javascript">var URL_BASE="{$webBridgeConfig['base']}";</script>
{/block}

{block name="analyticsJavascript"}
  {if strlen($GOOGLE_ANALYTICS_ID)}
    <script type="text/javascript">
      var _gaq = _gaq || []; {* suppress event tracking errors *}
    </script>
  {/if}
{/block}

{block name="inlineJavascriptBlocks"}
  {* will be called by onAjaxLoad() *}
{/block}

{block name="onLoadJavascriptBlocks"}
  {* will be called by onAjaxLoad() *}
{/block}

{block name="css"}
  <style type="text/css">
  * { -webkit-touch-callout: none; }
  </style>
  {$smarty.block.parent}
{/block}

{block name="javascript"}
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

{block name="viewportHeadTag"}
  {$scalable = false}
  {$smarty.block.parent}
{/block}

{block name="homeScreenIcon"}{/block}

{block name="onLoad"}
  onload="kgoBridge.ajaxLoad();"
{/block}

{block name="navbar"}{/block}
