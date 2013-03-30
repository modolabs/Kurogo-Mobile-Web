<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script type="text/javascript">var _sf_startpt=(new Date()).getTime()</script>
<meta http-equiv="Content-Type" content="text/html; charset={$charset}" />
<title>{block name="pageTitle"}{$strings.SITE_NAME|escape}{/block}</title>
<link type="text/css" href="{$minify['css']|escape}" rel="stylesheet" />
<link rel="shortcut icon" href="/favicon.ico" />
<meta name="description" http-equiv="description" content="{block name='description'}{/block}" />
</head>

<body>

<div id="container">
  <div id="header">
    {block name="header"}
  	<div id="utility_nav">
  		{if $links.homeURL}<a href="{$links.homeURL}" target="_blank">{$links.homeTitle}</a>
        {if $links.contactURL || $links.facebookURL || $links.twitterURL}&nbsp;|&nbsp;{/if}{/if}
        {if $links.contactURL}<a href="{$links.contactURL}" target="_blank">Contact</a>
        {/if}
        {if $links.facebookURL || $links.twitterURL}
        &nbsp;{if $links.homeURL || $links.contactURL}|&nbsp;{/if}
        <span class="share">Share:</span> &nbsp; {if $links.facebookURL}<a href="{$links.facebookURL}" title="Facebook" target="_blank"><img src="/modules/info/images/facebook.png" width="16" height="16" alt="facebook"></a>
		&nbsp;{/if}
		{if $links.twitterURL}
		<a href="{$links.twitterURL}" title="Twitter" target="_blank"><img src="/modules/info/images/twitter.png" width="16" height="16" alt="twitter"></a>
		{/if}
		{/if}
    </div><!--/utility_nav-->
    
    <div id="devices">
        <img src="/modules/info/images/devices.png" width="500" height="300" alt="{$strings.SITE_NAME|escape}" border="0" />
    </div>
    <div id="introduction">
       {block name="introduction"}
       {if $logo_image}
        <div id="logo">
            <img src="{$logo_image}" alt="{$strings.SITE_NAME|escape}" border="0" />
        </div>
        {/if}
        <p>{$moduleStrings.description} </p>
        {/block}
    </div>
    {/block}
  </div>

  <div id="content">
    {block name="content"}
  	<div class="leftcol">
    	<h2>{$moduleStrings.mobileWebTitle}</h2>
        <p>{$moduleStrings.mobileWebDescription}</p>
        <p>
          <a id="preview" class="roundbox" href="#" onclick="javascript:window.open('{$previewURL}','KurogoMobile','scrollbars=1,width=350,height=550');">Click here to preview the  mobile site on your computer.</a>
        </p>
        {if $moduleStrings.appsTitle}
    	<h2>{$moduleStrings.appsTitle}</h2>
        <p>
        	{$moduleStrings.appsDescription} 
        </p>
        <table cellpadding="0" cellspacing="0" id="download" align="right">
        {if $appData.iphone}
          <tr>
            <td>
            {$appData.iphone.downloadText}
            </td>
            <td>
            <a href="{$appData.iphone.url}" target="_blank"><img src="/modules/info/images/appstore.png" alt="" width="114" height="40" /></a>
            </td>
          </tr>
          {/if}
          {if $appData.android}
          <tr>
            <td>
            {$appData.android.downloadText}
            </td>
            <td>
            <a href="{$appData.android.url}" target="_blank"><img src="/modules/info/images/playstore.png" alt="" width="114" height="40" /></a>
            </td>
          </tr>
          {/if}
        </table>
        {/if}
        <div class="clr"></div>
        
    	<h2>{$moduleStrings.extraTitle}</h2>
        <p>
        {$moduleStrings.extraDescription}
        </p>
        
        <p>
          <a class="roundbox" id="feedback" href="mailto:{$strings.FEEDBACK_EMAIL}">
            <strong>{$moduleStrings.feedbackTitle}</strong>
            <br />
            {$moduleStrings.feedbackDescription}
          </a>
        </p>
        
    </div><!--/leftcol-->
    
    <div class="rightcol">
    	<h2>{$moduleStrings.modulesTitle}</h2>
        
    	<table cellpadding="0" cellspacing="0" id="features">
    	{foreach $modulesData as $moduleID=>$moduleData}
          <tr>
            <td>
              <img class="moduleicon" src="{if $info_icon_set}/common/images/iconsets/{$info_icon_set}/120/{$moduleData.icon}.png{else if $navigation_icon_set}/common/images/iconsets/{$navigation_icon_set}/120/{$moduleData.icon}.png{else}/modules/{$homeModuleID}/images/{$moduleData.icon}.png{/if}" alt="" />
            </td>
            <td>
            <h2>{$moduleData.title}</h2>
            <p>
            {$moduleData.description}
            </p>
            </td>
          </tr>
          {/foreach}
        </table>
    </div><!--/rightcol-->

	<div class="clr"></div>
	{/block}
  </div>

  <div id="footer">
    {block name="footer"}
		{if $strings.COPYRIGHT_LINK}
		  <a href="{$strings.COPYRIGHT_LINK}" class="copyright">
		{/if}
			{$strings.COPYRIGHT_NOTICE}
		{if $strings.COPYRIGHT_LINK}
		  </a>
		{/if}
		&nbsp;
	   {block name="footerKurogo"}{$footerKurogo}{/block}
    {/block}
  </div>

</div><!--/container-->

<script type="text/javascript">
  {block name="footerJavascript"}{/block}
</script>

</body>
</html>
