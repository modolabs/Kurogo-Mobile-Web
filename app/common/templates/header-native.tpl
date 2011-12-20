{extends file="findExtends:common/templates/header.tpl"}

{block name="pageTitle"}{$pageTitle|strip_tags|escape:'htmlall'}{/block}

{block name="urlBaseJavascript"}
  <script type="text/javascript">var URL_BASE='{$webBridgeServerURLBase}';</script>
{/block}

{block name="analyticsJavascript"}
{/block}

{block name="inlineJavascriptBlocks"}
  {* will be loaded below by content *}
{/block}

{block name="onLoadJavascriptBlocks"}
  {* will be loaded below by content *}
{/block}

{block name="javascript"}
  <script type="text/javascript">
    //
    // Native template for platform "{$platform}"
    //
    
    function ajaxLoad() {ldelim}
        var kurogoServerURL = '{$webBridgeServerURL}';
        var extraArgs       = '{$webBridgeServerArgs}';

        url = kurogoServerURL+'{$webBridgeServerPath}';
        if (extraArgs.length) {ldelim}
            url += '&'+extraArgs; // optional args set by native wrapper
        {rdelim}
        
        var httpRequest = new XMLHttpRequest();
        httpRequest.open("GET", url, true);
        httpRequest.onreadystatechange = function() {ldelim}
            if (httpRequest.readyState == 4 && httpRequest.status == 200) {ldelim}
                var container = document.getElementById("container");
                container.innerHTML = httpRequest.responseText;
                
                // Grab script tags and appendChild them so they get evaluated
                var scripts = container.getElementsByTagName("script");
                var count = scripts.length; // scripts.length will change as we add elements
                
                for (var i = 0; i < count; i++) {ldelim}
                    var script = document.createElement("script");
                    script.type = "text/javascript";
                    script.text = scripts[i].text;
                    container.appendChild(script);
                {rdelim}
                
                if (typeof onAjaxLoad != 'undefined') {ldelim}
                    onAjaxLoad();
                {rdelim} else {ldelim}
                    console.log("Warning! onAjaxLoad is not defined by the page content");
                {rdelim}
            {rdelim}
        {rdelim}
        httpRequest.send(null);
    {rdelim}
  </script>
  
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

{block name="ajaxContentHeader"}
  <script type="text/javascript">
    function onAjaxLoad() {ldelim}
      // These can all have per-content page behavior
      {foreach $inlineJavascriptFooterBlocks as $script}
        {$script}
      {/foreach}
      
      {foreach $onLoadBlocks as $script}
        {$script}
      {/foreach}
      
      onOrientationChange();
      
      {if $webBridgeOnPageLoadURL}
        window.location = "{$webBridgeOnPageLoadURL}";
      {/if}
    {rdelim}
  </script>
{/block}
