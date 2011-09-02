{include file="findInclude:common/templates/header.tpl"}

{block name="searchbox"}
{include file="findInclude:common/templates/search.tpl" placeholder="Search" resultCount=$resultCount tip=$searchTip|default:"You can search by part or all of a person's name, email address or phone number."}
{/block}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{block name="contactslist"}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
