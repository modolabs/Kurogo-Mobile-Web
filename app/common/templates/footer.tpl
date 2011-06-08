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
   {block name="footerKurogo"}Powered by Kurogo{/block}
  {/capture}

  
  {block name="footerNavLinks"}
    {if $moduleID != 'home'}
      <div id="footerlinks">
        <a href="#top">Back to top</a> | <a href="../home/">{$strings.SITE_NAME} home</a>
      </div>
    {/if}
  {/block}

  {block name="loginHTML"}
    {if $showLogin}
	<div class="loginstatus">
		<ul class="nav secondary loginbuttons">
        {if $session_isLoggedIn}
        {if $session_multiple_logins}
			<li><a href="{$session_logout_url}">Signed in with multiple identities</a></li>
        {else}
			<li class="{$session_authority_class}"><a href="../login">Signed in via {$session_authority_title} as {$session_fullName}{if $session_multiple_logins} (and other identities){/if}</a></li>
		{/if}
		{else}
			<li class="noauth"><a href="../login">Sign in to {$strings.SITE_NAME}</a></li>
		{/if}
		</ul>
	</div>
	{/if}
  {/block}

  {block name="footer"}
    <div id="footer">
      {$footerHTML}
    </div>
  {/block}

  {block name="deviceDetection"}
    {if $moduleID == 'home' && $showDeviceDetection}
      <table class="devicedetection">
        <tr><th>Pagetype:</th><td>{$pagetype}</td></tr>
        <tr><th>Platform:</th><td>{$platform}</td></tr>
        <tr><th>Certificates:</th><td>{if $supportsCerts}yes{else}no{/if}</td></tr>
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
