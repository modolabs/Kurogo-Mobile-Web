<div class="focal">
	{block name="byLine"}
	    <div class="byline">
	        <div class="avatar"><a href="{$authorURL}"><img class="social-avatar" src="{$authorImageURL}" alt="" /></a></div>
	        <div class="author"><a href="{$authorURL}">{$authorName}</a></div>
	    </div>
	{/block}

	{block name="postBody"}
    	<p class="post">{$postBody}</p>
	{/block}

	{block name="permalinks"}
    	<p class="timestamp"><a href="{$postURL}">{$postDate}</a> via <a href="http://{$service}.com"><img src="/common/images/button-{$service}.png" alt="" width="16" height="16" /> {ucwords($service)}</a></p>
	{/block}

	{block name="actionButtons"}
	    {if count($postLinks) > 0}
	        {include file="findInclude:common/templates/actionButtonList.tpl" actionButtons=$postLinks}
	    {/if}
	{/block}

</div>
