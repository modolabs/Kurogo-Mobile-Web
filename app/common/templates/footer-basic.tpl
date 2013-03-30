{extends file="findExtends:common/templates/footer.tpl"}

{block name="kgoFooterLinks"}
  {if $hasHelp}
    <p class="secondary">
      <a href="{$helpLink}">{$helpLinkText}</a>
    </p>
  {/if}
  
  {if $configModule != $homeModuleID || $page != 'index'}
    {html_access_key_reset index=0 force=true}
    <p class="bottomnav">
      <a href="#top">{$footerBackToTop}</a>
      <br />
      {html_access_key_link href="{$homeLink}"}{$homeLinkText}{/html_access_key_link}
      {if !$isModuleHome}
        {foreach $breadcrumbs as $breadcrumb}
          {if $configModule != $homeModuleID || !$breadcrumb@first}
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

{block name="kgoFooterLogin"}
  {if $showLogin}
    <div class="loginstatus">
      <p{if $footerLoginClass} class="{$footerLoginClass}"{/if}><a href="{$footerLoginLink}">{$footerLoginText}</a></p>
    </div>
  {/if}
{/block}

{block name="kgoFooterFontSizeSelection"}
  <div class="nonfocal">
    {include file="findInclude:common/templates/page/fontsize.tpl"}
    <p class="bb"> </p>
  </div>
{/block}

{block name="kgoFooterJavascript"}
  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
{/block}

{block name="kgoFooterAJAXContent"}{/block}
