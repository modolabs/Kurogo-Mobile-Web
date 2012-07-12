{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}{/block}

{block name="loginHTML"}{/block}

{block name="footer"}{/block}

{block name="deviceDetection"}{/block}

{block name="footerJavascript"}{* called on ajax load *}{/block}

{block name="ajaxContentFooter"}
  {$smarty.block.parent}
  
  <script type="text/javascript">
    kgoBridge.initPage({$webBridgeOnPageLoadParams});
  </script>
{/block}
