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
      // Native apps replace this with js which fixes per-OS/version issues
      // must be first inline js on page!
      {$webBridgeConfig['jsHeader']}
  </script>
  {$smarty.block.parent}
  <script type="text/javascript">
      var kgoBridgeConfig = {$webBridgeConfig['jsConfig']};
      {if $webBridgeJSLocalizedStrings}kgoBridgeConfig['localizedStrings'] = {$webBridgeJSLocalizedStrings};{/if} 
      var kgoBridge = new kgoBridgeHandler(kgoBridgeConfig);
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
