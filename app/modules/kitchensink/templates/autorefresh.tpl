{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{"AUTO_REFRESH_TEST_TITLE"|getLocalizedString}</h2>
</div>

<div class="focal">
  {"AUTO_REFRESH_TEST_INSTRUCTIONS"|getLocalizedString}
</div>

<div class="focal">
  {$lastUpdated}
</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links}

{include file="findInclude:common/templates/footer.tpl"}
