{extends file="modules/home/index.tpl"}

{block name="banner"}
  <h1><img id="logo" src="../modules/home/images/harvard-logo-home.gif" width="208" height="35" alt="{$SITE_NAME}" /></h1>
  <p class="bb"> </p>
{/block}

{block name="modulesStart"}{/block}

{block name="modulesCategoryStart"}<p class="nav {$category}">{/block}

{block name="module"}
  {strip}
  {if $category == 'primary'}
    {assign var=href value="../{$info['url']|default:$id}/"}
    {html_access_key_link href=$href}
      {$info['title']}
    {/html_access_key_link}
  {else}
    <a href="../{$info['url']|default:$id}/">
      {$info['title']}
    </a>
  {/if}
  {if isset($info['subTitle'])}
    <span class="smallprint"> ({$info['subTitle']})</span>
  {/if}
  <br/>
  {strip}
{/block}

{block name="modulesCategoryEnd"}</p>{/block}

{block name="modulesEnd"}{/block}
