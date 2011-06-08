{if $bookmarkURL}
<span id="bookmark" class="{$bookmarkStatus}">
    <img src="/common/images/bookmark-{$bookmarkStatus}.gif" alt="Bookmark">
    Bookmark
    <span class="fineprint">
        (<a href="{$bookmarkURL}">{$bookmarkAction}</a>)
    </span>
</span>
{/if}