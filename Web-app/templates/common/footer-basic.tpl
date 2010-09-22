{extends file="findExtends:common/footer.tpl"}

{block name="beforeLinks"}
  {strip}
  <p class="fontsize">
    Font size:&nbsp;
    {assign var=last value=end($fontsizes)}
    {foreach $fontsizes as $size}
      {if $size == $fontsize}
        <span class="font{$fontsize}">A</span>
      {else}
        <a href="{$fontSizeURL}{$size}" class="font{$size}">A</a>
      {/if}
      {if $size != $last} | {/if}
    {/foreach}
  </p>
  {/strip}
{/block}

{block name="beforeFooter"}
  <p class="bb"> </p>
{/block}

{block name="footerLinks"}
  {if $moduleID != 'home'}
    {html_access_key_reset index=0}
    <p class="bottomnav">
      <a href="#top">Back to top</a><br />
      {html_access_key_link href="../home/"}
        {$SITE_NAME} Home
      {/html_access_key_link}
      {if !$moduleHome}
        <br/>
        {html_access_key_link href="./"}
          {$moduleName} Home
        {/html_access_key_link}
      {/if}
      {foreach $breadcrumbs as $breadcrumb}
        <br />
        {html_access_key_link href=$breadcrumb['url']}
          {$breadcrumb['text']}
        {/html_access_key_link}
      {/foreach}
    </p>
  {/if}
{/block}

{block name="afterFooter"}{/block}

{block name="javascript"}{/block}

