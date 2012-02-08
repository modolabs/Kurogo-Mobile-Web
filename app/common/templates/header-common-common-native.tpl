{extends file="findExtends:common/templates/header.tpl"}

{block name="pageTitle"}{$pageTitle|strip_tags|escape:'htmlall'}{/block}

{block name="urlBaseJavascript"}
  <script type="text/javascript">var URL_BASE="{$webBridgeConfig['base']}";</script>
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
      // Native apps replace this with js which fixes per-OS/version issues
      // must be first inline js on page!
      {$webBridgeConfig['jsHeader']}
      
      //
      // Native template for platform "{$platform}"
      //
      
      function ajaxLoad() {ldelim}
          var kurogoServerPageURL  = 
              "{$webBridgeConfig['url']}{$webBridgeConfig['pagePath']}?{$webBridgeConfig['ajaxArgs']}";
          var kurogoServerPageArgs = "{$webBridgeConfig['pageArgs']}";
          if (kurogoServerPageArgs.length) {ldelim}
              kurogoServerPageURL += "&"+kurogoServerPageArgs; // optional args set by native wrapper
          {rdelim}

          var httpRequest = new XMLHttpRequest();
          httpRequest.open("GET", kurogoServerPageURL, true);
          
          var requestTimer = setTimeout(function() {ldelim}
              // some browsers set readyState to 4 on abort so remove handler first
              httpRequest.onreadystatechange = function() {ldelim} {rdelim};
              httpRequest.abort();
              
              onAjaxError(408); // http request timeout status code
          {rdelim}, {$webBridgeConfig['timeout']*1000});
          
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
          {if $webBridgeConfig['onError']}
              window.location = "{$webBridgeConfig['onError']}"+status;
          {/if}
      {rdelim}
      
      function webBridgeLinkToAjaxLinkIfNeeded(href) {ldelim}
          // must be able to pass through non-kgobridge links
          var bridgePrefix = "kgobridge://link/";
          if (href.indexOf(bridgePrefix) == 0) {ldelim}
              href = "{$webBridgeConfig['url']}/"+href.substr(bridgePrefix.length);
              
              var anchor = '';
              var anchorPos = href.indexOf("#");
              if (anchorPos > 0) {
                  anchor = href.substr(anchorPos);
                  href = href.substr(0, anchorPos);
              }
              href = href+(href.indexOf("?") > 0 ? "&" : "?")+"{$webBridgeConfig['ajaxArgs']}"+anchor;
          {rdelim}
          return href;
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
      // These can all have per-content page behavior
      {foreach $inlineJavascriptBlocks as $script}
          {$script}
      {/foreach}
  </script>
  <script type="text/javascript">
      {foreach $inlineJavascriptFooterBlocks as $script}
          {$script}
      {/foreach}
  </script>
  <script type="text/javascript">
      function onAjaxLoad() {ldelim}
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
