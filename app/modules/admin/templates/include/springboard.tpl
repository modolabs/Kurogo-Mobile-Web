<div class="springboard" {if $springboardID} id="{$springboardID}"{/if} section="{$section}">
  {foreach $springboardItems as $key=>$item}
  <div id="module_{$key}">
    <img src="{$item['img']}" alt="{$item['title']|escape}" />
    <input type="text" name="{$key}" value="{$item['title']|escape}"{if $section} section="{$section}"{/if} />
  </div>
  {/foreach}
</div>
<div class="springboard_clear"></div>
