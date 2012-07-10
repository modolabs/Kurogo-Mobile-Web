{include file="findInclude:common/templates/header.tpl"}

{foreach $lists as $listConfig}
  <div class="nonfocal">
    <h3>{$listConfig['description']}</h3>
  </div>
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$listConfig['items'] subTitleNewline=true}
{/foreach}

{include file="findInclude:common/templates/footer.tpl"}
