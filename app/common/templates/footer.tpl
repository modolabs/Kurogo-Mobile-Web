{if !$webBridgeAjaxContentLoad && !$ajaxContentLoad}
  {if $moduleDebug && count($moduleDebugStrings)}
    <p class="legend nonfocal">
      {foreach $moduleDebugStrings as $string}
        <br/>{$string}
      {/foreach}
    </p>  
  {/if}
  
  {capture name="footerHTML" assign="footerHTML"}
    {if $strings.COPYRIGHT_LINK}
      <a href="{$strings.COPYRIGHT_LINK}" class="copyright">
    {/if}
        {$strings.COPYRIGHT_NOTICE}
    {if $strings.COPYRIGHT_LINK}
      </a>
    {/if}
   <br />
   {block name="footerKurogo"}{$footerKurogo}{/block}
  {/capture}

  
  {block name="footerNavLinks"}
    {if !$hideFooterLinks}
      <div id="footerlinks">
        <a href="#top">{$footerBackToTop}</a> | <a href="{$homeLink}">{$homeLinkText}</a>
      </div>
    {/if}
  {/block}

  {block name="loginHTML"}
    {if $showLogin}
	<div class="loginstatus">
		<ul class="nav secondary loginbuttons">
		<li{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></li>
		</ul>
	</div>
	{/if}
  {/block}

  {block name="footer"}
    {if isset($customFooter)}
      {$customFooter|default:''}
    {else}
      <div id="footer">
        {$footerHTML}
      </div>
    {/if}
  {/block}

  {block name="deviceDetection"}
    {if $configModule == $homeModuleID && $showDeviceDetection}
      <table class="devicedetection">
        <tr><th>Pagetype:</th><td>{$pagetype}</td></tr>
        <tr><th>Platform:</th><td>{$platform}</td></tr>
        <tr><th>Platform:</th><td>{$browser}</td></tr>
        <tr><th>User Agent:</th><td>{$smarty.server.HTTP_USER_AGENT}</td></tr>
      </table>
    {/if}
  {/block}

  {block name="footerJavascript"}
    {foreach $inlineJavascriptFooterBlocks as $script}
      <script type="text/javascript">
        {$script} 
      </script>
    {/foreach}
    
    {if strlen($GOOGLE_ANALYTICS_ID)}
      <script type="text/javascript">
        (function() {ldelim}
          var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
          ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
          var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        {rdelim})();
      </script>
    {/if}
  {/block}
{block name="containerEnd"}
</div>
</div> <!--nonfooternav -->
{/block}

{block name="belowContent"}
{/block}
</body>
</html>
{else}
  {block name="ajaxContentFooter"}
    <script type="text/javascript">
      {foreach $inlineJavascriptFooterBlocks as $script}
        {$script}
      {/foreach}
      
      {foreach $onLoadBlocks as $script}
        {$script}
      {/foreach}
    
      {if count($onOrientationChangeBlocks)}
        addOnOrientationChangeCallback(function () {ldelim}
          {foreach $onOrientationChangeBlocks as $script}
            {$script}
          {/foreach}
        {rdelim});
      {/if}
      
      onOrientationChange();
    </script>
  {/block}
{/if}
