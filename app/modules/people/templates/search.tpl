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

{include file="findInclude:common/templates/search.tpl" emphasized=false inlineSearchError=$searchError additionalInputs=$selectSection}

{include file="findInclude:common/templates/results.tpl" results=$results accessKey=false}

{include file="findInclude:common/templates/footer.tpl"}

