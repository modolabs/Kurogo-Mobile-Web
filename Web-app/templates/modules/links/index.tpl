{include file="findInclude:common/header.tpl"}

<p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$linksPageConfig.INTRO_STRING}
</p>

{if $springboard}
  {include file="findInclude:common/springboard.tpl" springboardItems=$links springboardID="links"}
{else}
  {include file="findInclude:common/navlist.tpl" navlistItems=$links}
{/if}

<p class="clear"> </p>

{include file="findInclude:common/footer.tpl"}
