{extends file="findExtends:modules/$moduleID/templates/index-basic.tpl"}

{block name="topItem"}
  <p class="bb"> </p>  
{/block}

{block name="homeFooter"}
  <p class="bb"> </p>

{if $SHOW_DOWNLOAD}
  <div id="download">
    <a href="../download/">
      <img src="/modules/home/images/download.gif" 
      alt="Download" align="absmiddle" />
      Add the BlackBerry shortcut to your home screen
    </a>
    <br />
  </div>
{/if}
{/block}
