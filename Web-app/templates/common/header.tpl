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
  
  {block name="additionalHeadTags"}{/block}
</head>
<body>
{if $navbar|default:true}
  {block name="navHeader"}{/block}
{/if}

{if $startContainer|default:true}
  <div id="container">
{/if}