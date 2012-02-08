{if $item}
<div class="actionbutton">
  <a onclick="{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$configModule}', 'bookmark button pressed', '{$item|escape:'javascript'|escape:'html'}']);{/if}toggleBookmark('{$name}', '{$item|escape:'javascript'|escape:'html'}', {$exdate}, '{$smarty.const.COOKIE_PATH}')" ontouchstart="this.className='pressedaction'" ontouchend="this.className=''" {if $bookmarkStatus == "on"} class="on"{/if}>{"BOOKMARK"|getLocalizedString}</a>
</div>
{/if}
