  </div>
  
  {block name="footerLinks"}
    {if $moduleID != 'home'}
      <div id="footerlinks">
        <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>
      </div>
    {/if}
  {/block}

  {block name="footer"}
    <div id="footer">
      {$COPYRIGHT_NOTICE}
      {if $moduleID == 'home' && $showDeviceDetection}
        <p>
          <br/>
          Your user agent is "{$smarty.server.HTTP_USER_AGENT}"<br />
          You are classified as "{$pagetype}-{$platform}-{$supportsCerts}"<br />
          You {if !$supportsCerts}don't {/if}support certificates
        </p>
      {/if}
    </div>
  {/block}
  
  {block name="javascript"}
    {foreach $inlineJavascriptFooterBlocks as $script}
      <script type="text/javascript">
        {$script} 
      </script>
    {/foreach}
  {/block}

</body>
</html>
