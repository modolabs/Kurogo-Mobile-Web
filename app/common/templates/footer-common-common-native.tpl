{extends file="findExtends:common/templates/footer.tpl"}

{block name="kgoFooterLinks"}{/block}

{block name="kgoFooterLogin"}{/block}

{block name="kgoFooterCredits"}{/block}

{block name="kgoFooterDeviceDetection"}{/block}

{block name="kgoFooterJavascript"}{* called on ajax load *}{/block}

{block name="kgoFooterAJAXContent"}
  <script type="text/javascript">
    var URL_BASE='{$smarty.const.URL_BASE}';
    var API_URL_PREFIX='{$smarty.const.API_URL_PREFIX}';
    var KUROGO_PAGETYPE='{$pagetype}';
    var KUROGO_PLATFORM='{$platform}';
    var KUROGO_BROWSER='{$browser}';
  </script>

  <script type="text/javascript">
    kgoBridge.setConfig({$webBridgeOnPageLoadConfig});
  </script>

  {$smarty.block.parent}
  
  <script type="text/javascript">
    kgoBridge.initPage({$webBridgeOnPageLoadParams});
  </script>
{/block}
