{include file="common/header.tpl"|for_device:$device navbar=false startContainer=false}

{block name="banner"}
  <h1{if isset($topItem)} class="roomfornew"{/if}>
    <img src="../modules/home/images/harvard-logo-home.png" width="265" height="45" alt="{$SITE_NAME}" />
  </h1>

  {if isset($topItem)}
      <div id="new"><a href="../about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
  {/if}
{/block}

<div id="container">

{include file="common/search.tpl"|for_device:$device placeholder="Search "|cat:$SITE_NAME}  
  
{block name="modulesStart"}{/block}
  {assign var=lastCategory value=end(array_keys($homeModules))}
  {foreach $homeModules as $category => $moduleCategory}
    {block name="modulesCategoryStart"}{/block}
      {foreach $moduleCategory as $id => $info}
        {block name="module"}{/block}
      {/foreach}
    {block name="modulesCategoryEnd"}{/block}
  {/foreach}
{block name="modulesEnd"}{/block}

{include file="common/footer.tpl"|for_device:$device}
