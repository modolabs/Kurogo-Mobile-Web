{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="banner"}
  <h1><img id="logo" src="/modules/home/images/logo-home.gif" width="208" height="35" alt="{$SITE_NAME}" /></h1>
  <p class="bb"> </p>
{/block}

{block name="modulesList"}
  {strip}
  {foreach $homeModules as $category => $categoryModules}
    <p class="nav {$category}">
      {foreach $categoryModules as $id => $info}
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
      {/foreach}
    </p>
  {/foreach}
  {strip}
{/block}
