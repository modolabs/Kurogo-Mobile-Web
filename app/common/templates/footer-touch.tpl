{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}
  {if $moduleID != 'home'}
    <div id="footerlinks">
      <a href="#top">{$footerBackToTop}</a> 
      {if $hasHelp} | <a href="{$helpLink}">{$helpLinkText}</a>{/if}
       | <a href="{$homeLink}">{$homeLinkText}</a>
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
