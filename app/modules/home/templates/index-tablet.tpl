{block name="homeHeader"}
  {include file="findInclude:common/templates/header.tpl" scalable=false}
{/block}

{block name="userContextList"}{/block}
{block name="banner-notice"}
  {if $bannerNotice}
    <div class="banner-notice">
      {if $bannerURL}
        <a href="{$bannerURL}" class="banner-message">
      {else}
        <span class="banner-message">
      {/if}
        <img class="banner-icon" src="/common/images/alert@2x.png" alt="" />
        {$bannerNotice['title']}
      {if $bannerURL}
        </a>
      {else}
        </span>
      {/if}
    </div>
  {/if}
{/block}

<div class="portlets{if $bannerNotice} portlets-below-banner{/if}" id="home-portlets">
  {foreach $modulePanes as $portlet}
    <div class="portlet portlet-module-{$portlet['moduleId']}{if $portlet['classes']} {$portlet['classes']}{/if}">
      <div class="portlet-container">
        <div class="portlet-header">
          <a href="{$portlet['url']}">
            {$portlet['title']}
            <div class="portlet-nextlink"></div>
          </a>
        </div>
        <div id="{$portlet['elementId']}" class="portlet-content"><div class="loading"></div></div>
      </div>
    </div>
  {/foreach}
</div>

{include file="findInclude:common/templates/footer.tpl"}
