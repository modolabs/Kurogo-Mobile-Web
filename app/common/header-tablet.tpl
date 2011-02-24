{extends file="findExtends:common/header.tpl"}

{block name="viewportHeadTag"}
  <meta name="viewport" id="viewport" 
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0" />
{/block}

{block name="onLoad"} onload="tabletInit(); {if count($onLoadBlocks)}onLoad();{/if}"{/block}

{block name="navbar"}
  <div id="navbar">
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      <a name="top" href="/home/" class="homelink">
        <img src="/common/images/homelink.png" width="66" height="50" alt="Home" />
      </a>
      
      {$breadcrumbHTML}
      <span class="pagetitle">
        {if $isModuleHome}
          <img src="/common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" class="moduleicon" />
        {/if}
        {$pageTitle}
      </span>
    </div>
    {if $hasHelp}
      <div class="help">
        <a href="help.php"><img src="/common/images/help.png" width="52" height="50" alt="Help" /></a>
      </div>
    {/if}
  </div>
{/block}

{block name="containerStart"}
  <div id="wrapper">
    <div id="container">
      <div id="containerinset">
{/block}
