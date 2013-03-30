<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="{$charset}" />
<!--[if lt IE 9]>
<script src="{$http_protocol}://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
  {if $session_max_idle}
    <meta http-equiv="refresh" content="{$session_max_idle+2}" />
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
    <script type="text/javascript">var URL_BASE='{$smarty.const.URL_BASE}';</script>
    {foreach $inlineJavascriptBlocks as $inlineJavascriptBlock}
      <script type="text/javascript">{$inlineJavascriptBlock}</script>
    {/foreach}
    
    {foreach $javascriptURLs as $url}
      <script src="{$url}" type="text/javascript"></script>
    {/foreach}
    
    <script src="{$minify['js']}" type="text/javascript"></script>
</head>
<body>
<div id="pagewrap">
<header>
	<a href="{$smarty.const.URL_BASE}"><img src="/modules/admin/images/kurogo-logo.png" alt="Kurogo" width="90" height="90" id="logo" border="0" /></a>
	<h1>	
		{"KUROGO_ADMIN_TITLE"|getLocalizedString}
		<span id="sitename">{$strings.SITE_NAME}</span>
	</h1>
	<div id="utility">
        {if $session_isLoggedIn}<div id="user">{$footerLoginText} <a id="signout" href="{$session_logout_url}">{"SIGN_OUT"|getLocalizedString}</a></div>
        {/if}
	</div>
</header>

<div id="contentwrap">
	<nav>
		<ul>
		{foreach $navSections as $navSection}
        <li><a href="{$navSection.url}" title="{$navSection.description|escape}">{$navSection.title|escape}</a>
        {if $page==$navSection.id && $subNavSections}
        <ul>
		{foreach $subNavSections as $subNavSection}
            <li><a{if $subNavSection.id==$section} class="current"{/if} href="{$subNavSection.url}" section="{$subNavSection.id}">{if $subNavSection.img}<img src="{$subNavSection.img}" height="16" /> {/if}{$subNavSection.title|escape}</a></li>
        {/foreach}
        </ul>
        {/if}
        </li>
        {/foreach}
        </ul>
	</nav>
	
	<div id="content">
    <div id="message"></div>
