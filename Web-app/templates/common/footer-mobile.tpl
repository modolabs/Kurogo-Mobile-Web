{extends file="findExtends:common/footer.tpl"}

{block name="footerLinks"}
  {if $moduleID != 'home'}
    <div id="footerlinks">
      <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>
    </div>
  {/if}
{/block}

