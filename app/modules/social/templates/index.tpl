{include file="findInclude:common/templates/header.tpl"}
{if $needToAuth}
<p class="nonfocal">Some services require authorization before viewing posts</p>
{include file="findInclude:common/templates/navlist.tpl" navlistItems=$needToAuth subTitleNewline=true}
{/if}

{block name="postsHeader"}
  {if count($serviceLinks) > 0}
      <div class="nonfocal feedlinks">
        <span>{"FIND_US"|getLocalizedString}</span>
        {foreach $serviceLinks as $service => $link}
            <a href="{$link}" class="{$service}">{$service|strtoupper|getLocalizedString}</a>
        {/foreach}
      </div>
  {/if}
{/block}

{block name="firstPost"}
    {capture name="listitemSubTitle" assign="listitemSubTitle"}
        {if $firstPost['author']}<span class="author">{$firstPost['author']}</span>{/if}
        {if $firstPost['author'] && $firstPost['created']} | {/if}
        {if $firstPost['created']}<span class="post-date">{$firstPost['created']}</span>{/if}
    {/capture}
    {$firstPost['title'] = $firstPost['body']}
    {$firstPost['subtitle'] = $listitemSubTitle}

    <div class="focal featured {$firstPost['class']}">
        <h3>{"FEATURED_LABEL"|getLocalizedString}</h3>
        {include file="findInclude:modules/$moduleID/templates/postlistItem.tpl" item=$firstPost titleTruncate=$firstPostTitleTruncate}
    </div>
{/block}

{block name="posts"}
    {foreach $posts as $index=>$post}
        {capture name="listitemSubTitle" assign="listitemSubTitle"}
            {if $post['author']}<span class="author">{$post['author']}</span>{/if}
            {if $post['author'] && $post['created']} | {/if}
            {if $post['created']}<span class="post-date">{$post['created']}</span>{/if}
        {/capture}
        {$posts[$index]['title'] = $post['body']}
        {$posts[$index]['subtitle'] = $listitemSubTitle}
    {/foreach}

    {include file="findInclude:common/templates/results.tpl" listitemTemplateFile="findInclude:modules/$moduleID/templates/postlistItem.tpl" results=$posts subTitleNewline=true titleTruncate=$titleTruncate}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
