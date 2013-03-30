{extends file="findExtends:modules/home/templates/index.tpl"}

{block name="banner-notice"}
  {if $bannerNotice}
    <div class="banner-notice-basic">
      <img class="banner-icon" src="/common/images/alert{$imageExt}" />
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
{/block}

{block name="userContextList"}
 {include file="findInclude:common/templates/navlist.tpl" navlistItems=$userContextList accessKey=false secondary=true navListHeading=$userContextListDescription}
{/block}