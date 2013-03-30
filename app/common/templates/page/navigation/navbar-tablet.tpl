{extends file="findExtends:common/templates/page/navigation/navbar.tpl"}

{block name="kgoNavbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    {if isset($customNavmenuButton)}
      {$customNavmenuButton}
    {else}
      {include file="findInclude:common/templates/page/navigation/navmenuButton.tpl"}
    {/if}
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      {$kgoNavbarHomelink}
      {$kgoNavbarBreadcrumbsHTML}
      {$kgoNavbarPagetitle}
    </div>
    {include file="findInclude:common/templates/page/login.tpl"}
    {$kgoNavbarHelp}
  </div>
{/block}
