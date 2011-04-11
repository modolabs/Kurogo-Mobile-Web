<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="content-type" content="application/xhtml+xml" />
  {if $refreshPage}
    <meta http-equiv="refresh" content="{$refreshPage}" />
  {/if}
  <title>{$moduleName}{if !$isModuleHome}: {$pageTitle}{/if}</title>
  <link rel="shortcut icon" href="/favicon.ico" />
  <link href="{$minify['css']}" rel="stylesheet" media="all" type="text/css"/>
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
  {foreach $cssURLs as $cssURL}
    <link href="{$cssURL}" rel="stylesheet" media="all" type="text/css"/>
  {/foreach}
  
  {block name="javascript"}
      <script type="text/javascript">var URL_BASE='{$smarty.const.URL_BASE}';</script>
    {if strlen($GOOGLE_ANALYTICS_ID)}
      <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '{$GOOGLE_ANALYTICS_ID}']);
        _gaq.push(['_trackPageview']);
        
        (function() {ldelim}
          var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
          ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
          var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        {rdelim})();
      </script>
    {/if}
    
    {foreach $inlineJavascriptBlocks as $inlineJavascriptBlock}
      <script type="text/javascript">{$inlineJavascriptBlock}</script>
    {/foreach}
    
    {foreach $javascriptURLs as $url}
      <script src="{$url}" type="text/javascript"></script>
    {/foreach}
    
    <script src="{$minify['js']}" type="text/javascript"></script>

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

    {if count($onLoadBlocks)}
      <script type="text/javascript">
        function onLoad() {ldelim}
          {foreach $onLoadBlocks as $script}
            {$script}
          {/foreach}
        {rdelim}
      </script>
    {/if}
  {/block}
  
  {if !$autoPhoneNumberDetection}
  <meta name="format-detection" content="telephone=no">
  {/if}
  <meta name="HandheldFriendly" content="true" />
  {block name="viewportHeadTag"}
    <meta name="viewport" id="viewport" 
      content="width=device-width, {if $scalable|default:true}user-scalable=yes{else}user-scalable=no, initial-scale=1.0, maximum-scale=1.0{/if}" />
  {/block}
  <link rel="apple-touch-icon" href="/common/images/icon-{$configModule}.png" />
  {block name="additionalHeadTags"}{/block}
</head>

{capture name="breadcrumbHTML" assign="breadcrumbHTML"}
  {block name="breadcrumbs"}
    {if !$isModuleHome}
      {foreach $breadcrumbs as $breadcrumb}
        {if $breadcrumb@first}
          {$crumbClass = 'module'}
        {elseif count($breadcrumbs) == 1}
          {$crumbClass = 'crumb1'}
        {elseif count($breadcrumbs) == 2}
          {if !$breadcrumb@last}
            {$crumbClass = 'crumb2a'}
          {else}
            {assign var=crumbClass value='crumb2b'}
          {/if}
        {elseif count($breadcrumbs) > 2}
          {if $breadcrumb@last}
            {$crumbClass = 'crumb3c'}
          {elseif $breadcrumb@index == ($breadcrumb@total-2)}
            {assign var=crumbClass value='crumb3b'}
          {else}
            {assign var=crumbClass value='crumb3a'}
          {/if}
          
        {/if}
        <a href="{$breadcrumb['url']}" {if isset($crumbClass)}class="{$crumbClass}{/if}">
          {if $breadcrumb@first}
            <img src="/common/images/title-{$navImageID|default:$configModule}.png" width="28" height="28" alt="" />
          {else}
            <span>{$breadcrumb['title']}</span>
          {/if}
        </a>
      {/foreach}
    {/if}
  {/block}
{/capture}

<body class="{$configModule|capitalize}Module" 
  {block name="onLoad"}
    {if count($onLoadBlocks) || count($onOrientationChangeBlocks)}
      onload="{if count($onLoadBlocks)}onLoad();{/if}{if count($onOrientationChangeBlocks)}onOrientationChange();{/if}"
    {/if}
  {/block}>
  <div id="nonfooternav">
    <a name="top"></a>
    {if isset($customHeader)}
      {$customHeader|default:''}
    {else}
      {block name="navbar"}
        <div id="navbar"{if $hasHelp} class="helpon"{/if}>
          <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
            <a name="top" href="/home/" class="homelink">
              <img src="/common/images/homelink.png" width="57" height="45" alt="Home" />
            </a>
            
            {$breadcrumbHTML}
            <span class="pagetitle">
              {if $isModuleHome}
                <img src="/common/images/title-{$navImageID|default:$configModule}.png" width="28" height="28" alt="" class="moduleicon" />
              {/if}
              {$pageTitle}
            </span>
          </div>
          {if $hasHelp}
            <div class="help">
              <a href="help.php"><img src="/common/images/help.png" width="46" height="45" alt="Help" /></a>
            </div>
          {/if}
        </div>
      {/block}
    {/if}
    {block name="containerStart"}
      <div id="container">
    {/block}
