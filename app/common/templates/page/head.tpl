{*
 * CSS
 *}
 
{capture name="kgoHeadCSSHTML" assign="kgoHeadCSSHTML"}
  {block name="kgoHeadCSSMinify"}
    <link href="{$minify['css']|escape}" rel="stylesheet" media="all" type="text/css"/>
  {/block}
  
  {block name="kgoHeadCSSURLs"}
    {foreach $cssURLs as $cssURL}
      <link href="{$cssURL|escape}" rel="stylesheet" media="all" type="text/css"/>
    {/foreach}
  {/block}
  
  {block name="kgoHeadCSSInlineBlocks"}
    {foreach $inlineCSSBlocks as $css}
      <style type="text/css" media="screen">
        {$css}
      </style>
    {/foreach}
  {/block}
{/capture}

{*
 * Javascript
 *}

{capture name="kgoHeadJavascriptAJAXContentLoadingHTML" assign="kgoHeadJavascriptAJAXContentLoadingHTML"}{strip}
  {block name="kgoHeadJavascriptAJAXContentLoadingHTML"}
    <div class="loading"><div><img src="/common/images/loading.gif" width="27" height="21" alt="Loading" align="absmiddle" />{"AJAX_CONTENT_LOADING"|getLocalizedString}</div></div>
  {/block}
{/strip}{/capture}

{capture name="kgoHeadJavascriptAJAXContentErrorHTML" assign="kgoHeadJavascriptAJAXContentErrorHTML"}{strip}
  {block name="kgoHeadJavascriptAJAXContentErrorHTML"}
    <div class="nonfocal">{"AJAX_CONTENT_LOAD_FAILED"|getLocalizedString}</div>
  {/block}
{/strip}{/capture}

{capture name="kgoHeadJavascriptHTML" assign="kgoHeadJavascriptHTML"}
  {block name="kgoHeadJavascriptURLBase"}
    <script type="text/javascript">
      var URL_BASE='{$smarty.const.URL_BASE}';
      var API_URL_PREFIX='{$smarty.const.API_URL_PREFIX}';
      var KUROGO_PAGETYPE='{$pagetype}';
      var KUROGO_PLATFORM='{$platform}';
      var KUROGO_BROWSER='{$browser}';
    </script>
  {/block}
    
  {block name="kgoHeadJavascriptAJAX"}
    <script type="text/javascript">
      var AJAX_CONTENT_LOADING_HTML = '{$kgoHeadJavascriptAJAXContentLoadingHTML|escape:"quotes"}';
      var AJAX_CONTENT_ERROR_HTML = '{$kgoHeadJavascriptAJAXContentErrorHTML|escape:"quotes"}';
    </script>
  {/block}
  
  {block name="kgoHeadJavascriptAnalytics"}
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
  {/block}

  {block name="kgoHeadJavascriptURLs"}
    {foreach $javascriptURLs as $url}
      <script src="{$url|escape}" type="text/javascript"></script>
    {/foreach}
  {/block}
    
  {block name="kgoHeadJavascriptMinify"}
    <script src="{$minify['js']|escape}" type="text/javascript"></script>
  {/block}
    
  {block name="kgoHeadJavascriptBlocks"}
    {foreach $inlineJavascriptBlocks as $inlineJavascriptBlock}
      <script type="text/javascript">{$inlineJavascriptBlock}</script>
    {/foreach}
  {/block}
    
  {block name="kgoHeadJavascriptOnOrientationChange"}
    <script type="text/javascript">
      setupOrientationChangeHandlers();
      {if count($onOrientationChangeBlocks)}
        addOnOrientationChangeCallback(function () {ldelim}
          {foreach $onOrientationChangeBlocks as $script}
            {$script}
          {/foreach}
        {rdelim});
      {/if}
    </script>
  {/block}
    
  {block name="kgoHeadJavascriptOnLoadBlocks"}
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
{/capture}

{*
 * head elements
 *}
{block name="kgoHeadContentType"}
  <meta http-equiv="content-type" content="application/xhtml+xml" charset="{$charset}" />
{/block}

{block name="kgoHeadRefreshPage"}
  {if $refreshPage}
    <meta http-equiv="refresh" content="{$refreshPage}" />
  {/if}
{/block}

{block name="kgoHeadPageTitle"}
  <title>{if !$isModuleHome}{$moduleName}: {/if}{$pageTitle|strip_tags|escape:'htmlall'}</title>
{/block}

{block name="kgoHeadShortcutIcon"}
  <link rel="shortcut icon" href="/favicon.ico" />
{/block}

{block name="kgoHeadCSS"}
  {$kgoHeadCSSHTML}
{/block}

{block name="kgoHeadJavascript"}
  {$kgoHeadJavascriptHTML}
{/block}

{block name="kgoHeadPhoneNumberDetection"}
  {if !$autoPhoneNumberDetection}
    <meta name="format-detection" content="telephone=no" />
  {/if}
{/block}

{block name="kgoHeadHandheldFriendly"}
  <meta name="HandheldFriendly" content="true" />
{/block}

{block name="kgoHeadViewportTag"}
  <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0, {if $scalable|default:false}user-scalable=yes, maximum-scale=2.0{else}user-scalable=no, maximum-scale=1.0{/if}" />
{/block}

{block name="kgoHeadHomeScreenIcon"}
  <link rel="apple-touch-icon" href="{$smarty.const.FULL_URL_BASE|nosecure}common/images/icon.png" />
  <link rel="apple-touch-icon-precomposed" href="{$smarty.const.FULL_URL_BASE|nosecure}common/images/icon.png" />
{/block}

{block name="kgoHeadAdditionalTags"}{/block}
