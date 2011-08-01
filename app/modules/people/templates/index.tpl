{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" resultCount=$resultCount tip=$searchTip}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false}

{include file="findInclude:common/templates/footer.tpl"}
