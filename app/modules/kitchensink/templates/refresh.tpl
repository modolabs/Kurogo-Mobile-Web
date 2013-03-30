{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h2>{"REFRESH_TEST_TITLE"|getLocalizedString}</h2>
</div>

<div class="focal">
  {"REFRESH_TEST_INSTRUCTIONS"|getLocalizedString}
</div>

<div class="focal">
  {$lastUpdated}
</div>

{include file="findInclude:common/templates/footer.tpl"}
