{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}
  {if $hasHelp}
    <p class="secondary">
      <a href="{$helpLink}">{$helpLinkText}</a>
    </p>
  {/if}
  
  {if $moduleID != 'home' || $page != 'index'}
    {html_access_key_reset index=0 force=true}
    <p class="bottomnav">
      <a href="#top">{$footerBackToTop}</a>
      <br />
      {html_access_key_link href="{$homeLink}"}{$homeLinkText}{/html_access_key_link}
      {if !$isModuleHome}
        {foreach $breadcrumbs as $breadcrumb}
          {if $moduleID != 'home' || !$breadcrumb@first}
            <br/>
            {html_access_key_link href=$breadcrumb['url']}
              {if $breadcrumb@first}
                {$moduleHomeLinkText}
              {else}
                {$breadcrumb['longTitle']}
              {/if}
            {/html_access_key_link}
          {/if}
        {/foreach}
      {/if}
      {foreach $additionalLinks as $link}
        <br/>
        {html_access_key_link href=$link['url']}{$link['title']}{/html_access_key_link}
      {/foreach}
    </p>
  {/if}

{/block}

  {block name="loginHTML"}
    {if $session && $moduleID == 'home'}
	<div class="loginstatus">
		<p{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></p>
	</div>
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
  {if strlen($pmImageURL)}
    <img src="{$pmImageURL}" width="2" height="2" alt="" />
  {/if}

{/block}
