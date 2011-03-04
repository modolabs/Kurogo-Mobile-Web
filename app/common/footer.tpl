  {if $moduleDebug && count($moduleDebugStrings)}
    <p class="legend nonfocal">
      {foreach $moduleDebugStrings as $string}
        <br/>{$string}
      {/foreach}
    </p>  
  {/if}
  
  {capture name="footerHTML" assign="footerHTML"}
    {if $COPYRIGHT_LINK}
      <a href="{$COPYRIGHT_LINK}" class="copyright">
    {/if}
        {$COPYRIGHT_NOTICE}
    {if $COPYRIGHT_LINK}
      </a>
    {/if}
  {/capture}

  {capture name="loginHTML" assign="loginHTML"}
    {if $session}<div id="loginInfo"><a href="../login/{if !$session->isLoggedIn()}?url={$request_uri}{/if}">{if $session->isLoggedIn()}{$session_user->getFullName()}{if $session_authority_image} <img src="{$session_authority_image}" alt="{$session_authority_title|escape}" />{else} ({$session_authority_title}){/if}{else}Not{/if} logged in</a></div>{/if}
  {/capture}
  
  {block name="footerNavLinks"}
    {if $moduleID != 'home'}
      <div id="footerlinks">
        <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>
        {$loginHTML}
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
  {/block}
{block name="containerEnd"}
</div>
</div> <!--nonfooternav -->
{/block}

{block name="belowContent"}
{/block}
</body>
</html>
