<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <meta http-equiv="content-type" content="application/xhtml+xml" />
  {if $refreshPage}
    <meta http-equiv="refresh" content="{$refreshPage}" />
  {/if}
  <title>{if !$isModuleHome}{$moduleName}: {/if}{$pageTitle|strip_tags|escape:'htmlall'}</title>
  <link rel="shortcut icon" href="/favicon.ico" />
  <link href="{$minify['css']|escape}" rel="stylesheet" media="all" type="text/css"/>
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
  {foreach $cssURLs as $cssURL}
    <link href="{$cssURL|escape}" rel="stylesheet" media="all" type="text/css"/>
  {/foreach}
  
  {block name="javascript"}
      <script type="text/javascript">var URL_BASE='{$smarty.const.URL_BASE}';</script>
    {if strlen($GOOGLE_ANALYTICS_ID)}
      <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '{$GOOGLE_ANALYTICS_ID}']);
        {if $GOOGLE_ANALYTICS_DOMAIN}
        _gaq.push(['_setDomainName', '{$GOOGLE_ANALYTICS_DOMAIN}']);
        {/if}
        _gaq.push(['_trackPageview']);
      </script>
    {/if}
    {if strlen($PERCENT_MOBILE_ID)}
        <script src="{$PERCENT_MOBILE_URL}" type="text/javascript" charset="utf-8"></script>
    {/if}
    
    {foreach $inlineJavascriptBlocks as $inlineJavascriptBlock}
      <script type="text/javascript">{$inlineJavascriptBlock}</script>
    {/foreach}
    
    {foreach $javascriptURLs as $url}
      <script src="{$url|escape}" type="text/javascript"></script>
    {/foreach}
    
    <script src="{$minify['js']|escape}" type="text/javascript"></script>

    <script type="text/javascript">
      function onOrientationChange() {ldelim}
        {* the galaxy tab sends orientation change events constantly *}
        if (typeof onOrientationChange.lastOrientation == 'undefined') {ldelim}
          onOrientationChange.lastOrientation = null;
        {rdelim}
        var newOrientation = getOrientation();
        if (newOrientation != onOrientationChange.lastOrientation) {ldelim}
          rotateScreen();
          {foreach $onOrientationChangeBlocks as $script}
            {$script}
          {/foreach}
        {rdelim}
        onOrientationChange.lastOrientation = newOrientation;
      {rdelim}
      if (window.addEventListener) {ldelim}
        window.addEventListener("orientationchange", onOrientationChange, false);
      {rdelim} else if (window.attachEvent) {ldelim}
        window.attachEvent("onorientationchange", onOrientationChange);
      {rdelim}
      {if count($onOrientationChangeBlocks)}
        function onResize() {ldelim}
          {foreach $onOrientationChangeBlocks as $script}
            {$script}
          {/foreach}
        {rdelim}
        if (window.addEventListener) {ldelim}
          window.addEventListener("resize", onResize, false);
        {rdelim} else if (window.attachEvent) {ldelim}
          window.attachEvent("onresize", onResize);
        {rdelim}
      {/if}
    </script>
    
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
  {block name="homeScreenIcon"}
  <link rel="apple-touch-icon" href="{$smarty.const.FULL_URL_BASE|nosecure}common/images/icon.png" />
  <link rel="apple-touch-icon-precomposed" href="{$smarty.const.FULL_URL_BASE|nosecure}common/images/icon.png" />
  {/block}
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
        {if $moduleID != 'home' || !$breadcrumb@first}
          <a href="{$breadcrumb['url']|sanitize_url}" {if isset($crumbClass)}class="{$crumbClass}{/if}">
            {if $breadcrumb@first}
              <img src="/common/images/title-{$navImageID|default:$configModule}.png" width="{$module_nav_image_width|default:28}" height="{$module_nav_image_height|default:28}" alt="" />
            {else}
              <span>{$breadcrumb['title']|sanitize_html:'inline'}</span>
            {/if}
          </a>
        {/if}
      {/foreach}
    {/if}
  {/block}
{/capture}

<body class="{$configModule|capitalize}Module" 
  {block name="onLoad"}
    {if count($onLoadBlocks) || count($onOrientationChangeBlocks)}
      onload="{if count($onLoadBlocks)}onLoad();{/if}onOrientationChange();"
    {/if}
  {/block}>
  <div id="nonfooternav">
    <a name="top"> </a>
    {if isset($customHeader)}
      {$customHeader|default:''}
    {else}
      {block name="navbar"}
        <div id="navbar"{if $hasHelp} class="helpon"{/if}>
          <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
            <a href="{$homeLink}" class="homelink" title="{$homeLinkText}">
              <img src="/common/images/homelink.png" width="{$homelink_image_width|default:57}" height="{$homelink_image_height|default:45}" alt="{$homeLinkText}" />
            </a>
            
            {$breadcrumbHTML}
            <span class="pagetitle">
              {if $isModuleHome}
                <img src="/common/images/title-{$navImageID|default:$configModule}.png" width="{$module_nav_image_width|default:28}" height="{$module_nav_image_height|default:28}" alt="" class="moduleicon" />
              {/if}
              {$pageTitle|sanitize_html:'inline'}
            </span>
          </div>
          {if $hasHelp}
            <div class="help">
              <a href="{$helpLink}" title="{$helpLinkText}"><img src="/common/images/help.png" width="{$help_image_width|default:46}" height="{$help_image_height|default:45}" alt="{$helpLinkText}" /></a>
            </div>
          {/if}
        </div>
      {/block}
    {/if}
    {block name="containerStart"}
      <div id="container">
    {/block}
