{if $lastUpdated}
<div class="nonfocal">Stats includes data up until {$lastUpdated}. <a href="{$updateStatsLink}">Click here to update statistics</a></div>
{else}
<div class="nonfocal">Stats have never been updated. <a href="{$updateStatsLink}">Click here to update statistics</a></div>
{/if}
