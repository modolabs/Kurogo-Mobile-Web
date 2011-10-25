{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}{/block}

{block name="loginHTML"}{/block}

{block name="footer"}{/block}

{block name="deviceDetection"}{/block}

{block name="footerJavascript"}
  {$GOOGLE_ANALYTICS_ID = ''}
  {$PERCENT_MOBILE_ID = ''}
  {$smarty.block.parent}
{/block}