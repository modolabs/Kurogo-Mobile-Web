{extends file="findExtends:common/footer.tpl"}

{block name="footerLinks"}
  {strip}
  <p class="fontsize">
    Font size:&nbsp;
    {foreach $fontsizes as $size}
      {if $size == $fontsize}
        <span class="font{$fontsize}">A</span>
      {else}
        <a href="{$fontSizeURL}{$size}" class="font{$size}">A</a>
      {/if}
      {if !$size@last} | {/if}
    {/foreach}
  </p>

  {if $moduleID != 'home'}
    {html_access_key_reset index=0}
    <p class="bottomnav">
      <a href="#top">Back to top</a>
      <br />
      {html_access_key_link href="/home/"}{$SITE_NAME} Home{/html_access_key_link}
      {if !$moduleHome}
        <br/>
        {html_access_key_link href="/$moduleID/"}{$moduleName} Home{/html_access_key_link}
      {/if}
      {foreach $breadcrumbs as $breadcrumb}
        <br />
        {html_access_key_link href=$breadcrumb['url']}{$breadcrumb['text']}{/html_access_key_link}
      {/foreach}
    </p>
  {/if}
  {/strip}
{/block}

{block name="footer"}
  <p class="bb"> </p>
  {$smarty.block.parent}
{/block}

{block name="javascript"}
{/block}

