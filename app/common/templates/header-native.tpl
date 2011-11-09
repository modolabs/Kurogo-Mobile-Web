{extends file="findExtends:common/templates/header.tpl"}

{block name="javascript"}
  <script type="text/javascript">
    //
    // Native template for platform "{$platform}"
    //
    
    function ajaxLoad() {ldelim}
        var kurogoServerURL = '__KUROGO_SERVER_URL__';
        var extraArgs       = '__KUROGO_MODULE_EXTRA_ARGS__';

        url = kurogoServerURL+'/{$configModule}/{$page}?ajax=1&nativePlatform={$platform}';
        if (extraArgs.length) {ldelim}
            url += '&'+extraArgs; // optional args set by native wrapper
        {rdelim}
        
        var httpRequest = new XMLHttpRequest();
        httpRequest.open("GET", url, true);
        httpRequest.onreadystatechange = function() {ldelim}
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {ldelim}
                document.getElementById('container').innerHTML = httpRequest.responseText;
                onAjaxLoad();
            {rdelim}
        {rdelim}
        httpRequest.send(null);
    {rdelim}
    
    function onAjaxLoad() {ldelim}
      {foreach $inlineJavascriptBlocks as $script}
        {$script}
      {/foreach}
      
      {foreach $inlineJavascriptFooterBlocks as $script}
        {$script}
      {/foreach}
      
      {foreach $onLoadBlocks as $script}
        {$script}
      {/foreach}
      
      onOrientationChange();
    {rdelim}
  </script>
  
  {$URL_BASE = '__KUROGO_URL_BASE__'}
  {$GOOGLE_ANALYTICS_ID = ''}
  {$PERCENT_MOBILE_ID = ''}
  {$inlineJavascriptBlocks = null}
  {$inlineJavascriptFooterBlocks = null}
  {$onLoadBlocks = null}
  {$smarty.block.parent}
{/block}

{block name="viewportHeadTag"}
  {$scalable = false}
  {$smarty.block.parent}
{/block}

{block name="homeScreenIcon"}{/block}

{block name="onLoad"}
  onload="ajaxLoad();"
{/block}

{block name="navbar"}{/block}
