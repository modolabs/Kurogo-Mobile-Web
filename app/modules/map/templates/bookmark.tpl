{if $item}
<div class="actionbutton bookmarkbutton {if $bookmarkStatus == "on"} on{/if}" id="bookmarkplace" >
<a href="javascript:void(0)" onclick="{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$configModule}', 'bookmark button pressed', '{$item|escape:'javascript'|escape:'html'}']);{/if}toggleBookmark('{$name}', '{$item|escape:'javascript'|escape:'html'}', {$exdate}, '{$smarty.const.COOKIE_PATH}', 'bookmarkplace')" ontouchstart="addClass(this, 'pressedaction')" ontouchend="removeClass(this, 'pressedaction')">{"BOOKMARK"|getLocalizedString}</a>
</div>
{/if}
