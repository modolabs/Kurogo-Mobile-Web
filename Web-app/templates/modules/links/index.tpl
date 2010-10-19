{include file="findInclude:common/header.tpl"}

<p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$linksPageConfig.INTRO_STRING}
</p>

{block name="linksStart"}
  <div id="springboard">
{/block}

    {foreach $links as $id => $link}
      {block name="link"}
        <div id="{$id}">
          <a href="{$link['url']}">
            <img src="/modules/{$moduleID}/images/{$link['icon']}" width="50" height="50" alt="{$link['title']}" />
            <br/>
            {$link['title']}
          </a>
        </div>
      {/block}
    {/foreach}
  
{block name="linksEnd"}
  </div>
{/block}

<p class="clear"> </p>

{include file="findInclude:common/footer.tpl"}
