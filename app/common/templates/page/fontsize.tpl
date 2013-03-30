<p class="fontsize">
  {"FONT_SIZE_SELECTION_TITLE"|getLocalizedString}&nbsp;
  {foreach $fontsizes as $size}
    {if $size == $fontsize}
      <span class="font{$fontsize}">A</span>
    {else}
      <a href="{$fontSizeURLs[$size]}" class="font{$size}">A</a>
    {/if}
    {if !$size@last}&nbsp;|&nbsp;{/if}
  {/foreach}
</p>
