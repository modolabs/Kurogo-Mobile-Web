{extends file="findExtends:common/templates/footer.tpl"}

{block name="footer"}
  <p class="fontsize">
    Font size:&nbsp;
    {foreach $fontsizes as $size}
      {if $size == $fontsize}
        <span class="font{$fontsize}">A</span>
      {else}
        <a href="{$fontSizeURLs[$size]}" class="font{$size}">A</a>
      {/if}
      {if !$size@last}&nbsp;|&nbsp;{/if}
    {/foreach}
  </p>
  
  {$smarty.block.parent}
{/block}
