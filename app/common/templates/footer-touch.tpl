{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}
  {if $moduleID != 'home'}
    <div id="footerlinks">
      <a href="#top">Back to top</a> 
      {if $hasHelp} | <a href="help.php">Help</a>{/if}
       | <a href="../home/">{$strings.SITE_NAME} home</a>
      {if $session_userID} | <a href="../login">{$session_userID} logged in</a>{/if}
    </div>
  {/if}
{/block}

{block name="footerJavascript"}
  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
  {if strlen($pmImageURL)}
    <img src="{$pmImageURL}" width="2" height="2" alt="" />
  {/if}
{/block}
