
{* This is the segmented selector for which latest news *}
{if count($latestSubTabLinks) > 1}
	{block name="groupSelector"}
		<ul class="tabstrip twotabs subtab">
		{foreach $latestSubTabLinks as $tabItem}
			<li{if $latestSubTab == $tabItem.id} class="active"{/if}>
				<a href="{$tabItem.url}" onclick="updateSubTab(this, '{$tabstripId}', '{$tabItem.ajaxUrl}'); return false;">{$tabItem.title}</a>
			</li>
		{/foreach}
		</ul>
	{/block}
{/if}

{* This is where the tab content comes from *}
{if $latestSubTab == 'topnews'}
	{include file="findInclude:common/templates/search.tpl"}
	{include file="findInclude:modules/athletics/templates/stories.tpl" stories=$topNews}
{elseif $latestSubTab == 'allschedule'}
	{include file="findInclude:common/templates/results.tpl" results=$scheduleItems subTitleNewline=true}
{/if}