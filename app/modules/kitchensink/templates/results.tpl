{include file="findInclude:common/templates/header.tpl"}

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
