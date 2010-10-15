{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="banner"}
  <h1><img id="logo" src="/modules/home/images/logo-home.gif" width="208" height="35" alt="{$SITE_NAME}" /></h1>
  <p class="bb"> </p>
{/block}

{block name="modulesStart"}
{/block}

  {block name="categoryStartTag"}
    <p class="nav {$category}">
  {/block}

      {block name="module"}
        {if $category == 'primary'}
          {html_access_key_link href=$info['url']}
            {$info['title']}
          {/html_access_key_link}
        {else}
          <a href="{$info['url']}">
            {$info['title']}
          </a>
        {/if}
        {if isset($info['subTitle'])}
          <span class="smallprint"> ({$info['subTitle']})</span>
        {/if}
        <br/>
      {/block}

  {block name="categoryEnd"}
    </p>
  {/block}

{block name="modulesEndTag"}
{/block}
