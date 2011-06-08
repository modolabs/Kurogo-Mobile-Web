{extends file="findExtends:modules/calendar/templates/detail.tpl"}

{block name="firstField"}
  <h2>
    {include file="findInclude:common/templates/listItem.tpl" item=$firstField}
  </h2>
{/block}

{block name="fields"}
  {foreach $fields as $item}
    <p>
      {include file="findInclude:common/templates/listItem.tpl" subTitleNewline=false}
    <p>
  {/foreach}
{/block}

