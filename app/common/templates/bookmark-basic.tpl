{if $bookmarkURL}
<span id="bookmark" class="nonfocal">
    {"BOOKMARK_LINK"|getLocalizedString}&nbsp;
    <span class="fineprint">
        (<a href="{$bookmarkURL}">{$bookmarkAction}</a>)
    </span>
</span>
{/if}