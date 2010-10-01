{extends file="findExtends:common/footer.tpl"}

{block name="footer"}
  <div class="nonfocal">

    {if $hasHelp}
      <p class="secondary">
        <a href="help.php">{$moduleName} Help</a>
      </p>
    {/if}
  
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
          {html_access_key_link href=$breadcrumb['url']}{$breadcrumb['longTitle']}{/html_access_key_link}
        {/foreach}
        {if isset($additionalLinks)}
          {foreach $additionalLinks as $link}
            <br />
            {html_access_key_link href=$link['url']}{$link['title']}{/html_access_key_link}
          {/foreach}
        {/if}
      </p>
    {/if}
  
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

    <p class="bb"> </p>
    
    <p id="footer">
      {$footerHTML}
    </p>

  </div>
  
{/block}
