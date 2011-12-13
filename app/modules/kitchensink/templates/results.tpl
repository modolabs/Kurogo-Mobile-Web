{include file="findInclude:common/templates/header.tpl"}

{capture name="sideNav" assign="sideNav"}
{if $prevURL || $nextURL}
  <div class="{block name='sideNavClass'}sidenav2{/block}">
    {if $prevURL && $prev}
      <a href="{$prevURL}" class="sidenav-prev">
        {block name="prevPrefix"}{/block}
        {$prev}
      </a>{block name="sidenavSpacer"} {/block}
    {/if}
    {if $nextURL && $next}
      <a href="{$nextURL}" class="sidenav-next">
        {$next}
        {block name="nextSuffix"}{/block}
      </a>
    {/if}
  </div>
{/if}
{/capture}

<div class="nonfocal">
  <h3>Results Pager:</h3>
</div>
{$sideNav}

<div class="nonfocal">
  <h3>Empty Results list:</h3>
</div>
{$empty = array()}
{include file="findInclude:common/templates/results.tpl" results=$empty}

{foreach $lists as $listConfig}
  <div class="nonfocal">
    <h3>{$listConfig['description']}</h3>
  </div>
  {include file="findInclude:common/templates/results.tpl" results=$listConfig['items'] subTitleNewline=true}
{/foreach}

{include file="findInclude:common/templates/footer.tpl"}
