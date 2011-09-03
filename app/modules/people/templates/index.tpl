{include file="findInclude:common/templates/header.tpl"}

{block name="searchbox"}
{include file="findInclude:common/templates/search.tpl" resultCount=$resultCount tip=$searchTip}
{/block}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{block name="contactslist"}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
