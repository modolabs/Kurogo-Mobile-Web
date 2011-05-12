{include file="findInclude:common/templates/header.tpl"}

{include file="findInclude:common/templates/search.tpl" placeholder="Search" resultCount=$resultCount tip="You can search by part or all of a person's name, email address or phone number."}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false}

{include file="findInclude:common/templates/footer.tpl"}
