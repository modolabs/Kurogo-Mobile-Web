{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}{/block}

{block name="loginHTML"}{/block}

{block name="footer"}{/block}

{block name="deviceDetection"}{/block}

{block name="footerJavascript"}{/block}

{block name="ajaxContentFooter"}
  {if $nativePageConfigURL}
    <img src="/common/images/blank.png" onload="window.location = '{$nativePageConfigURL}';" style="display:none" />
  {/if}
{/block}
