<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="content-type" content="application/xhtml+xml" />
  <title>{$moduleName}{if !$moduleHome}: {$pageTitle}{/if}</title>
  <link href="{$minify['css']}" rel="stylesheet" media="all" type="text/css"/>
  
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
  
  {block name="javascript"}
    <script src="{$minify['js']}" type="text/javascript"></script>
  
    {foreach $inlineJavascriptBlocks as $script}
      <script type="text/javascript">
        {$script} 
      </script>
    {/foreach}  
    
    {if count($onOrientationChangeBlocks)}
      <script type="text/javascript">
        function onOrientationChange() {ldelim}
          {foreach $onOrientationChangeBlocks as $script}
            {$script}
          {/foreach}
        {rdelim}
        window.addEventListener("orientationchange", onOrientationChange, false);
        window.addEventListener("resize", onOrientationChange, false);
      </script>
    {/if}
  {/block}
  
  {block name="deviceHeadTags"}{/block}
</head>
<body>
  {if isset($customHeader)}
    {$banner|default:''}
  {else}
    {block name="header"}
      {strip}
      <div id="navbar"{if $help} class="helpon"{/if}>
        <div class="breadcrumbs{if $moduleHome} homepage{/if}">
          <a name="top" href="/home/" class="homelink">
            <img src="/common/images/homelink.png" width="57" height="45" alt="{$SITE_NAME} Home" />
          </a>
          
          {if !$moduleHome}
            <a href="./" class="module">
              <img src="/common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" />
            </a>
            {foreach $breadcrumbs as $breadcrumb}
              <a href="{$breadcrumb['url']}" class="{$breadcrumb['class']}">
                <span>{$breadcrumb['text']}</span>
              </a>
            {/foreach}
          {/if}
          <span class="pagetitle">
            {if $moduleHome}
              <img src="/common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" class="moduleicon" />
            {/if}
            {$pageTitle}
          </span>
        </div>
        {if $showHelp}
          <div class="help">
            <a href="help.php"><img src="/common/images/help.png" width="46" height="45" alt="Help" /></a>
          </div>
        {/if}
      </div>
      {/strip}
    {/block}
  {/if}
  
  <div id="container">
