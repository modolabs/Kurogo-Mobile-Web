{if $item}
<div id="bookmarkContainer">
  <a onclick="{if strlen($GOOGLE_ANALYTICS_ID)}_gaq.push(['_trackEvent', '{$configModule}', 'bookmark button pressed', '{$item|escape:'javascript'|escape:'html'}']);{/if}toggleBookmark('{$name}', '{$item|escape:'javascript'|escape:'html'}', {$exdate}, '{$smarty.const.COOKIE_PATH}')">
    <div id="bookmark" ontouchstart="addClass(this, 'pressed')" ontouchend="removeClass(this, 'pressed')"{if $bookmarkStatus == "on"} class="on"{/if}></div>
  </a>
</div>
{/if}
