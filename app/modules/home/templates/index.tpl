{capture name="banner" assign="banner"}
  {block name="banner-notice"}
    {if $bannerNotice}
      <div class="banner-notice">
        {if $bannerURL}
          <a href="{$bannerURL}" class="banner-message">
        {else}
          <span class="banner-message">
        {/if}
          <img class="banner-icon" src="/common/images/alert{$imageExt}" />
          {$bannerNotice['title']}
        {if $bannerURL}
          </a>
        {else}
          </span>
        {/if}
      </div>
    {/if}
  {/block}
  {block name="banner-logo"}
    <h1 id="homelogo" class="{if isset($topItem)}roomfornew{/if} {$home_banner_class}">
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

<div id="homemodules">
{include file="findInclude:modules/home/templates/include/modules.tpl"}
</div>

{block name="homeFooter"}
{/block}

{block name="downloadText"}
  {if $SHOW_DOWNLOAD_TEXT}
    <p id="download">
      <a href="/download/">
        <img src="{$downloadImgPrefix}{$imageExt}" alt="" align="absmiddle" />
        {$SHOW_DOWNLOAD_TEXT}
      </a>
      <br />
    </p>
  {/if}  
{/block}

{block name="userContextList"}
{if $userContextList}
{include file="findInclude:common/templates/page/navigation/userContextList.tpl" navContainerID="homemodules"}
{/if}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
