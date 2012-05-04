{capture name="banner" assign="banner"}
  {block name="banner-notice"}
  {if $bannerNotice}
  <div class="banner-notice">
    {if $bannerURL}
      <a href="{$bannerURL}" class="banner-message">
    {else}
      <span class="banner-message">
    {/if}
      {$bannerNotice['title']}
    {if $bannerURL}
      </a>
    {else}
      </span>
    {/if}
    </div>
  {/if}
  <h1 id="homelogo"{if isset($topItem)} class="roomfornew"{/if}>
    <img src="/modules/{$configModule}/images/logo-home{$imageExt}" width="{$banner_width|default:265}" height="{$banner_height|default:45}" alt="{$strings.SITE_NAME|escape}" />
  </h1>
  {/block}
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$banner scalable=false}

{if $showFederatedSearch}
{block name="federatedSearch"}
{include file="findInclude:common/templates/search.tpl"}
{/block}
{/if}

{if $displayType == 'springboard'}
  {include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules springboardID="homegrid"}
  
{elseif $displayType == 'list'}
  {$primaryModules = array()}
  {$secondaryModules = array()}
  {$foundSeparator = false}
  {foreach $modules as $module}
    {if $module['separator']}
      {$foundSeparator = true}
    {elseif $foundSeparator}
      {$secondaryModules[] = $module}
    {else}
      {$primaryModules[] = $module}
    {/if}
  {/foreach}

  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$primaryModules}
  {if $secondaryModules}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$secondaryModules accessKeyLink=false}
  {/if}
{/if}

{block name="homeFooter"}
{/block}

{block name="downloadText"}
{if $SHOW_DOWNLOAD_TEXT}
<p id="download">
    <a href="/download/">
      <img src="/modules/{$configModule}/images/download{$imageExt}"
      alt="" align="absmiddle" />
      {$SHOW_DOWNLOAD_TEXT}
    </a>
    <br />
</p>
{/if}  
{/block}

{include file="findInclude:common/templates/footer.tpl"}
