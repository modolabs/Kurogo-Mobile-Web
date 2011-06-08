{extends file="findExtends:modules/$moduleID/templates/index-basic.tpl"}

{block name="topItem"}
  <p class="bb"> </p>  
{/block}

{block name="homeFooter"}
  <p class="bb"> </p>

{if $SHOW_DOWNLOAD_TEXT}
  <div id="download">
    <a href="../download/">
      <img src="/modules/home/images/download.gif" 
      alt="Download" align="absmiddle" />
      {$SHOW_DOWNLOAD_TEXT}
    </a>
    <br />
  </div>
{/if}
{/block}
