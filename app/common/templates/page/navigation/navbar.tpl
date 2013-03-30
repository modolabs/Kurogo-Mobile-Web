{capture name="kgoNavbarBreadcrumbsHTML" assign="kgoNavbarBreadcrumbsHTML"}
  {block name="kgoNavbarBreadcrumbs"}
    {if isset($customNavbarBreadcrumbsHTML)}
      {$customNavbarBreadcrumbsHTML}
    {else}
      {if !$isModuleHome}
        {if count($breadcrumbs) && !$breadcrumbsShowAll}
          {$breadcrumb = reset($breadcrumbs)}
          {$breadcrumbs = array()}
          {$breadcrumbs[] = $breadcrumb}
        {/if}
        {foreach $breadcrumbs as $breadcrumb}
          {if $breadcrumb@first}
            {$crumbClass = 'module'}
          {elseif count($breadcrumbs) == 1}
            {$crumbClass = 'crumb1'}
          {elseif count($breadcrumbs) == 2}
            {if !$breadcrumb@last}
              {$crumbClass = 'crumb2a'}
            {else}
              {assign var=crumbClass value='crumb2b'}
            {/if}
          {elseif count($breadcrumbs) > 2}
            {if $breadcrumb@last}
              {$crumbClass = 'crumb3c'}
            {elseif $breadcrumb@index == ($breadcrumb@total-2)}
              {assign var=crumbClass value='crumb3b'}
            {else}
              {assign var=crumbClass value='crumb3a'}
            {/if}
            
          {/if}
          {if $configModule != $homeModuleID || !$breadcrumb@first}
            <a href="{$breadcrumb['url']|sanitize_url}" {if isset($crumbClass)}class="{$crumbClass}"{/if}>
              {if $breadcrumb@first}
                <img src="/common/images/{if $title_icon_set}iconsets/{$title_icon_set}/{$title_icon_size}/{else}title-{/if}{$navImageID|default:$configModule}.png" width="{$module_nav_image_width|default:30}" height="{$module_nav_image_height|default:30}" alt="" />
              {else}
                <span>{$breadcrumb['title']|sanitize_html:'inline'}</span>
              {/if}
            </a>
          {/if}
        {/foreach}
      {/if}
    {/if}
  {/block}
{/capture}

{capture name="kgoNavbarModuleHomeIconHTML" assign="kgoNavbarModuleHomeIconHTML"}
  {block name="kgoNavbarModuleHomeIcon"}
    {if $isModuleHome}
      <img src="/common/images/{if $title_icon_set}iconsets/{$title_icon_set}/{$title_icon_size}/{else}title-{/if}{$navImageID|default:$configModule}.png" width="{$module_nav_image_width|default:30}" height="{$module_nav_image_height|default:30}" alt="" class="moduleicon" />
    {/if}
  {/block}
{/capture}

{capture name="kgoNavbarHomelink" assign="kgoNavbarHomelink"}
  {block name="kgoNavbarHomelink"}
    {if isset($customNavbarHomelink)}
      {$customNavbarHomelink}
    {else}
      <a href="{$homeLink}" class="homelink" title="{$homeLinkText}">
        {$useWideHomeLink = $homelink_use_wide_image || 
                            ($configModule == $homeModuleID && $isModuleHome && 
                             $homelink_use_wide_image_sitehome)}
        <img src="/common/images/homelink{if $useWideHomeLink}-wide{/if}.png" width="{if $useWideHomeLink}{$homelink_wide_image_width}{else}{$homelink_image_width}{/if}" height="{if $useWideHomeLink}{$homelink_wide_image_height}{else}{$homelink_image_height}{/if}" alt="{$homeLinkText}" />
      </a>
    {/if}
  {/block}
{/capture}

{capture name="kgoNavbarPagetitle" assign="kgoNavbarPagetitle"}
  {block name="kgoNavbarPagetitle"}
    {if isset($customNavbarPagetitle)}
      {$customNavbarPagetitle}
    {else}
      <span class="pagetitle">
        {$kgoNavbarModuleHomeIconHTML}
        {$pageTitle|sanitize_html:'inline'}
      </span>
    {/if}
  {/block}
{/capture}

{capture name="kgoNavbarHelp" assign="kgoNavbarHelp"}
  {if $hasHelp}
    {block name="kgoNavbarHelp"}
      {if isset($customNavbarHelp)}
        {$customNavbarHelp}
      {else}
        <div class="help">
          <a href="{$helpLink}" title="{$helpLinkText}"><img src="/common/images/help.png" width="{$help_image_width|default:46}" height="{$help_image_height|default:45}" alt="{$helpLinkText}" /></a>
        </div>
      {/if}
    {/block}
  {/if}
{/capture}

{block name="kgoNavbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      {$kgoNavbarHomelink}
      {$kgoNavbarBreadcrumbsHTML}
      {$kgoNavbarPagetitle}
    </div>
    {$kgoNavbarHelp}
  </div>
{/block}
