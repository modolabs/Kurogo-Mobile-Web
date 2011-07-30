{extends file="findExtends:common/templates/header.tpl"}

{block name="javascript"}{/block}
{block name="onLoad"}{/block}

{block name="breadcrumbs"}
  {if !$isModuleHome && $configModule != 'home'}
    <a href="./" class="moduleicon">
      <img src="/common/images/title-{$navImageID|default:$configModule}.gif" width="28" height="28" alt="" />
    </a>
  {/if}
{/block}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      <a name="top" href="{$homeLink}" class="homelink">
        <img src="/common/images/homelink.gif" width="40" height="30" alt="{$homeLinkText}" />
      </a>
      {$breadcrumbHTML}
        {if $isModuleHome}
          <img src="/common/images/title-{$navImageID|default:$configModule}.gif" width="28" height="28" alt="" class="moduleicon" />
        {/if}
      <span class="pagetitle">
        {$pageTitle}
      </span>
    </div>
  </div>
{/block}
