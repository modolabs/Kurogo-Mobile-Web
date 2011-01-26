{extends file="findExtends:modules/news/story.tpl"}

{block name="shareImage"}{/block}

{block name="byline"}
  {$smarty.block.parent}
  <a href="{$shareUrl}">Email this article</a>
{/block}
