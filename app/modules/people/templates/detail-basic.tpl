{extends file="findExtends:modules/people/templates/detail.tpl"}

{block name="headerClass"}focal header{/block}
{block name="bookmarksClass"}focal{/block}

{block name="headerPhoto"}{/block}

{block name="itemLabel"}
  <strong>{$item['label']}</strong><br />
{/block}

{block name="itemTitle"}
  {$item['title']}
{/block}
