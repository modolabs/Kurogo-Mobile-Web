{extends file="findExtends:modules/calendar/detail.tpl"}

{block name="firstField"}
  <h2>
    {include file="findInclude:common/listItem.tpl" item=$firstNavlistItem}
  </h2>
{/block}

{block name="fields"}
  {foreach $fields as $item}
    <p>
      {include file="findInclude:common/listItem.tpl" subTitleNewline=false}
    <p>
  {/foreach}
{/block}

