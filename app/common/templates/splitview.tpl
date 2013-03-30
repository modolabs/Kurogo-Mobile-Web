<div{if $splitview['id']} id="{$splitview['id']}"{/if} class="splitview{if $splitview['class']} {$splitview['class']}{/if}">
  <div class="splitview-listwrapper">
    <div class="splitview-list">{$splitview['list']}</div>
  </div>
  <div class="splitview-detailwrapper">
    <div class="splitview-detail">{$splitview['detail']}</div>
  </div>
</div>
