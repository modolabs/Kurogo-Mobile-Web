  {strip}
  
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
    {if $moduleID == 'home' && $showDeviceDetection}
      <br/>
      Your user agent is "{$smarty.server.HTTP_USER_AGENT}"<br />
      You are classified as "{$pagetype}-{$platform}-{$supportsCerts}"<br />
      You {if !$supportsCerts}don't {/if}support certificates
    {/if}
  {/capture}
  
  {block name="footer"}

    {if $moduleID != 'home'}
      <div id="footerlinks">
        <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>
      </div>
    {/if}

    <div id="footer">
      {$footerHTML}
    </div>

    {foreach $inlineJavascriptFooterBlocks as $script}
      <script type="text/javascript">
        {$script} 
      </script>
    {/foreach}

  {/block}

  {/strip}
</div>
</body>
</html>
