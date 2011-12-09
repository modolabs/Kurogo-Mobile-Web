{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h3>Navigation list:</h3>
</div>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$items}

<div class="nonfocal">
  <h3>Results list:</h3>
</div>
{include file="findInclude:common/templates/results.tpl" results=$items}

{include file="findInclude:common/templates/footer.tpl"}

