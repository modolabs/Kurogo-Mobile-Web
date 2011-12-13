{if $bookmarks}
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$bookmarks}
{else}
    <p>{"NO_BOOKMARKS"|getLocalizedString}</p>
{/if}