{extends file="findExtends:modules/home/templates/index.tpl"}

{block name="homeFooter"}
{if $SHOW_DOWNLOAD}
  <div id="download">
    <a href="../download/">
      <img src="/modules/{$moduleID}/images/download.png" width="32" height="26" 
      alt="Download" align="absmiddle" />
      Add the BlackBerry shortcut to your home screen
    </a>
    <br />
  </div>
{/if}  
{/block}
