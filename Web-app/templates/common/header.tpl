<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="content-type" content="application/xhtml+xml" />
  <meta name="viewport" id="viewport" 
    content="width=device-width, user-scalable={$scalable|default:yes}{if $fixed} initial-scale=1.0, maximum-scale=1.0{/if}" />
  <title>{$title}</title>
  <link href="{$minify['css']}" rel="stylesheet" media="all" type="text/css"/>
  <script src="{$minify['js']}" type="text/javascript"></script>
  <link rel="apple-touch-icon" href="common/images/icon-{$moduleID}.png" />
  {$stylesheetLinks}
  
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
  
  {foreach $inlineJavascriptBlocks as $script}
    <script type="text/javascript">
      {$script} 
    </script>
  {/foreach}  
  
  {foreach $javascriptFiles as $file}
    <script src="{$file}.js" type="text/javascript"></script>
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
</head>
<body>
{if $navbar|default:true}
  {include file="common/navbar.tpl"|for_device:$device}
{/if}

  <div id="container">