{include file="common/header.tpl"|for_device:$device}

<p class="nonfocal smallprint">
  Click on a shield to see each School's website. Please note that these websites may not be optimized for mobile.
</p>
<div id="springboard">
  {foreach $links as $link}
    <div id="{$link['name']}">
      <a href="{$link['url']}">
        <img src="modules/{$moduleID}/images/{$link['icon']}" width="50" height="50" alt="{$link['name']}" />
        <br/>
        {$link['name']}
      </a>
    </div>
  {/foreach}
</div>

<p class="clear"> </p>

{include file="common/footer.tpl"|for_device:$device}
