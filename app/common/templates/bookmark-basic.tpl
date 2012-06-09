{if $bookmarkURL}
<span id="bookmark" class="nonfocal">
    <img src="/common/images/bookmark-{$bookmarkStatus}.gif" alt="Bookmark">
    {"BOOKMARK_LINK"|getLocalizedString}&nbsp;
    <span class="fineprint">
        (<a href="{$bookmarkURL}">{$bookmarkAction}</a>)
    </span>
</span>
{/if}
