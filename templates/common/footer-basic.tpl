{extends file="findExtends:common/footer.tpl"}

{block name="footerNavLinks"}
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
    {html_access_key_reset index=0 force=true}
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

{/block}

{block name="footer"}
  <div class="nonfocal">
    <p class="fontsize">
      Font size:&nbsp;
      {foreach $fontsizes as $size}
        {if $size == $fontsize}
          <span class="font{$fontsize}">A</span>
        {else}
          <a href="{$fontSizeURLs[$size]}" class="font{$size}">A</a>
        {/if}
        {if !$size@last}&nbsp;|&nbsp;{/if}
      {/foreach}
    </p>

    <p class="bb"> </p>
    
    <p id="footer">
      {$footerHTML}
    </p>
  </div>
{/block}

{block name="footerJavascript"}
  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
{/block}
