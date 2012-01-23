{extends file="findExtends:common/templates/header.tpl"}

{block name="pageTitle"}{$pageTitle|strip_tags|escape:'htmlall'}{/block}

{block name="urlBaseJavascript"}
  <script type="text/javascript">var URL_BASE='{$webBridgeServerURLBase}';</script>
{/block}

{block name="analyticsJavascript"}
  {if strlen($GOOGLE_ANALYTICS_ID)}
    <script type="text/javascript">
      var _gaq = _gaq || []; {* suppress event tracking errors *}
    </script>
  {/if}
{/block}

{block name="inlineJavascriptBlocks"}
  {* will be called by onAjaxLoad() *}
{/block}

{block name="onLoadJavascriptBlocks"}
  {* will be called by onAjaxLoad() *}
{/block}

{block name="javascript"}
  <script type="text/javascript">
      //
      // Native template for platform "{$platform}"
      //
      
      function ajaxLoad() {ldelim}
          var kurogoServerURL  = "{$webBridgeServerURL}";
          var kurogoServerArgs = "{$webBridgeServerArgs}";
          if (kurogoServerArgs.length) {ldelim}
              kurogoServerURL += "&"+kurogoServerArgs; // optional args set by native wrapper
          {rdelim}

          var httpRequest = new XMLHttpRequest();
          httpRequest.open("GET", kurogoServerURL, true);
          
          var requestTimer = setTimeout(function() {ldelim}
              // some browsers set readyState to 4 on abort so remove handler first
              httpRequest.onreadystatechange = function() {ldelim} {rdelim};
              httpRequest.abort();
              
              onAjaxError(408); // http request timeout status code
          {rdelim}, {$webBridgeServerTimeout*1000});
          
          httpRequest.onreadystatechange = function() {ldelim}
              // return if still in progress
              if (httpRequest.readyState != 4) {ldelim} return; {rdelim}
              
              // Got answer, don't abort
              clearTimeout(requestTimer);
              
              if (httpRequest.status == 200) {ldelim}
                  // Success
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
                  
              {rdelim} else {ldelim}
                  // Error
                  if (typeof onAjaxError != 'undefined') {ldelim}
                      onAjaxError(httpRequest.status);
                  {rdelim} else {ldelim}
                      console.log("Warning! onAjaxError is not defined by the page content");
                  {rdelim}
              {rdelim}
          {rdelim}
          
          httpRequest.send(null);
      {rdelim}
    
      function onAjaxError(status) {ldelim}
          {if $webBridgeOnLoadErrorURL}
              window.location = "{$webBridgeOnLoadErrorURL}"+status;
          {/if}
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
          
          {if $webBridgeOnPageLoadURL}
              window.location = "{$webBridgeOnPageLoadURL}";
          {/if}
      {rdelim}
  </script>
{/block}
