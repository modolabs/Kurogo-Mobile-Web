{include file="findInclude:common/templates/header.tpl"}

{capture name="selectSection" assign="selectSection"}
{if $feeds|@count>1}
    <select id="feed" name="feed">
	{foreach $feeds as $feed => $title}
		<option value="{$feed}"{if $selectedFeed==$feed} selected="selected"{/if}>{"PEOPLE_SEARCH_IN"|getLocalizedString:$title}</option>
	{/foreach}
	</select>
{/if}
{/capture}

{block name="searchbox"}
{include file="findInclude:common/templates/search.tpl" resultCount=$resultCount tip=$searchTip additionalInputs=$selectSection}
{/block}

{if $hasBookmarks}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarkLink secondary=true}
{/if}

{block name="contactslist"}
{if $contacts}
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false subTitleNewline=$contactsSubTitleNewline}
{/if}
{/block}

{include file="findInclude:common/templates/footer.tpl"}