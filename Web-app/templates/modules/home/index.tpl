{include file="findInclude:common/header.tpl" navbar=false startContainer=false}

{block name="banner"}
  <h1{if isset($topItem)} class="roomfornew"{/if}>
    <img src="/modules/home/images/harvard-logo-home.png" width="265" height="45" alt="{$SITE_NAME}" />
  </h1>

  {if isset($topItem)}
      <div id="new"><a href="/about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
  {/if}
{/block}

<div id="container">

{include file="findInclude:common/search.tpl" placeholder="Search "|cat:$SITE_NAME}  
  
{block name="modules"}
  {assign var=lastCategory value=end(array_keys($homeModules))}
  {foreach $homeModules as $category => $categoryModules}
    {block name="category"}
      {foreach $categoryModules as $id => $info}
        {block name="module"}{/block}
      {/foreach}
    {/block}
  {/foreach}
{/block}

{include file="findInclude:common/footer.tpl"}
