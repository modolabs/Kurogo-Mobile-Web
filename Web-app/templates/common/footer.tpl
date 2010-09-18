  </div>
  
  {if $moduleID != 'home'}
    <div id="footerlinks">
      <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>
    </div>
  {/if}

  <div id="footer">
    {$COPYRIGHT_NOTICE}
  </div>
  
  {foreach $inlineJavascriptFooterBlocks as $script}
    <script type="text/javascript">
      {$script} 
    </script>
  {/foreach}  

</body>
</html>
