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

{block name="navheader"}
  <div class="nonfocal">
    <h2>{$fullTitle}</h2>
    <p class="smallprint">{$albumcount} {if $albumcount == 1} {"PICTURE_TEXT"|getLocalizedString} {else} {"PICTURES_TEXT"|getLocalizedString} {/if}</p>
  </div>
  {$sideNav}
{/block}

{block name="springboard"}
  {include file="findInclude:modules/photos/templates/springboard.tpl" springboardItems=$photos}
{/block}


{$sideNav}

{include file="findInclude:common/templates/footer.tpl"}
