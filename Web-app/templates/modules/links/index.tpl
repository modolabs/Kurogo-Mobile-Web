{include file="common/header.tpl"|for_device:$device}

{block name="textStart"}<p class="nonfocal smallprint">{/block}
  Click on a shield to see each School's website. Please note that these websites may not be optimized for mobile.
{block name="textEnd"}</p>{/block}

{block name="linksStart"}<div id="springboard">{/block}
  {foreach $links as $link}
    {block name="link"}
      <div id="{$link['name']}">
        <a href="{$link['url']}">
          <img src="modules/{$moduleID}/images/{$link['icon']}" width="50" height="50" alt="{$link['name']}" />
          <br/>
          {$link['name']}
        </a>
      </div>
    {/block}
  {/foreach}
{block name="linksEnd"}</div>{/block}

<p class="clear"> </p>

{include file="common/footer.tpl"|for_device:$device}
