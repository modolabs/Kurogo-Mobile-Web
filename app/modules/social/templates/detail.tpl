{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
    <p class="post">{$postBody}</p>
    <div class="byline">
        <a href="{$authorURL}"><img class="social-avatar" src="{$authorImageURL}" alt="" /></a>
        <div class="author-created">
            <div class="author"><a href="{$authorURL}">{$authorName}</a></div>
            <div class="created">{$postDate}</div>
        </div>
    </div>
    {if count($postLinks) > 0}
        {include file="findInclude:common/templates/actionButtonList.tpl" actionButtons=$postLinks}
    {/if}
</div>

{include file="findInclude:common/templates/footer.tpl"}
