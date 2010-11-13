<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
{strip}  
<head>
  <meta http-equiv="content-type" content="application/xhtml+xml" />
  <title>{$moduleName}{if !$isModuleHome}: {$pageTitle}{/if}</title>
  <link href="{$minify['css']}" rel="stylesheet" media="all" type="text/css"/>
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
  
  {block name="javascript"}
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
    
    {foreach $inlineJavascriptBlocks as $script}
      <script type="text/javascript">
        {$script} 
      </script>
    {/foreach}  
    
    {foreach $externalJavascriptURLs as $url}
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
  
  {block name="additionalHeadTags"}{/block}
</head>
<body{block name="onLoad"}{if count($onLoadBlocks)} onload="onLoad();"{/if}{/block}>
  <a name="top"></a>
  {if isset($customHeader)}
    {$customHeader|default:''}
  {else}
    {block name="header"}
      {strip}
      <div id="navbar"{if $hasHelp} class="helpon"{/if}>
        <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
          <a name="top" href="/home/" class="homelink">
            <img src="/common/images/homelink.png" width="57" height="45" alt="{$SITE_NAME} Home" />
          </a>
          
          {if !$isModuleHome}
            {if $moduleID != 'home'}
              <a href="./" class="module">
                <img src="/common/images/title-{$navImageID|default:$moduleID}.png"   width="28" height="28" alt="" />
              </a>
            {/if}
            {foreach $breadcrumbs as $breadcrumb}
              {if count($breadcrumbs) == 1}
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
                <span>{$breadcrumb['title']}</span>
              </a>
            {/foreach}
          {/if}
          <span class="pagetitle">
            {if $isModuleHome}
              <img src="/common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" class="moduleicon" />
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
      {/strip}
    {/block}
  {/if}
{/strip}  
  <div id="container">
