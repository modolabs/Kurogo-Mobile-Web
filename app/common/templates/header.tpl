{if !$webBridgeAjaxContentLoad && !$ajaxContentLoad}<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  {include file="findInclude:common/templates/page/head.tpl"}
</head>

{block name="kgoHeaderNavigationBodyClasses"}
  {$kgoHasNavmenu = count($navigationModules) > 0}
  {$kgoHasNavbar = !isset($customHeader)}
{/block}
<body class="{$configModule|capitalize}Module{if $configModule!=$moduleID} {$moduleID|capitalize}Module{/if}{if $moduleFillScreen} fillscreen{/if}{if $kgoHasNavmenu} kgo-has-navmenu{/if}{if $kgoHasNavbar} kgo-has-navbar{/if}{if $configModule == $homeModuleID && $page == 'index'} kgo-site-homepage{/if}" 
  {block name="kgoHeaderBodyAttributes"}
    {if count($onLoadBlocks) || count($onOrientationChangeBlocks)}
      onload="{if count($onLoadBlocks)}onLoad();{/if}onOrientationChange();"
    {/if}
  {/block}>
  <div id="kgo_accessibility_links">
  {block name="kgoAccessibilityLinks"}
  {/block}
  </div>
  <div id="nonfooternav">
    {if isset($customHeader)}
      {block name="kgoHeaderTopAnchor"}
        <a name="top"> </a>
      {/block}
      {$customHeader}
    {else}
      {block name="kgoHeaderNavigation"}
        {include file="findInclude:common/templates/page/navigation/navbar.tpl"}
      {/block}
    {/if}
    
    {block name="kgoHeaderContainerStart"}
      <div id="container">
    {/block}
{else}
  {block name="kgoHeaderAJAXContent"}
    {foreach $inlineCSSBlocks as $css}
      <style type="text/css" media="screen">
        {$css}
      </style>
    {/foreach}
    
    <script type="text/javascript">
      {foreach $inlineJavascriptBlocks as $script}
        {$script}
      {/foreach}
    </script>
  {/block}
{/if}
{block name="kgoContentTopAnchor"}
<a name="content_top" id="content_top"></a>
{/block}
