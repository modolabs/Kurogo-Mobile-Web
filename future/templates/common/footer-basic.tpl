{extends file="findExtends:common/footer.tpl"}

{block name="footer"}
  <div class="nonfocal">

    {if $hasHelp}
      <p class="secondary">
        <a href="help.php">{$moduleName} Help</a>
      </p>
    {/if}
    
    {capture name="breadcrumbSeparator" assign="breadcrumbSeparator"}
      {block name="breadcrumbSeparator"}
        <br />
      {/block}
    {/capture}
    
    {if $moduleID != 'home'}
      {html_access_key_reset index=0}
      <p class="bottomnav">
        <a href="#top">Back to top</a>
        {$breadcrumbSeparator}
        {html_access_key_link href="/home/"}{$SITE_NAME} Home{/html_access_key_link}
        {if !$isModuleHome}
          {$breadcrumbSeparator}
          {html_access_key_link href="/$moduleID/"}{$moduleName} Home{/html_access_key_link}
        {/if}
        {if count($breadcrumbs)}
          <br/>
          {foreach $breadcrumbs as $breadcrumb}
            {if !$breadcrumb@first}{$breadcrumbSeparator}{/if}
            {html_access_key_link href=$breadcrumb['url']}{$breadcrumb['longTitle']}{/html_access_key_link}
          {/foreach}
        {/if}
        {if isset($additionalLinks)}
          <br/>
          {foreach $additionalLinks as $link}
            {if !$link@first}{$breadcrumbSeparator}{/if}
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
  
  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
{/block}

{block name="footerJavascript"}
{/block}
