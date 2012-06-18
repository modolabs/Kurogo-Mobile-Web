{capture name="banner" assign="banner"}
  <div id="navbar">
    <img src="/modules/{$configModule}/images/home.png" width="40" height="40" alt="" class="moduleicon" />
    <span class="pagetitle">
      {$strings.SITE_NAME}
    </span>        
    {include file="findInclude:common/templates/search.tpl" inNavbar=true placeholder="Search "|cat:$strings.SITE_NAME}
  </div>
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$banner scalable=false}

<div class="blocks" id="fillscreen">
  {foreach $modulePanes as $blockName => $blockConfig}
    <div class="block {$blockName} {$blockConfig['id']}">
      <div class="blockborder">
        <a href="{$blockConfig['url']}" class="blockheader">
          <div class="icon" style="background-image: url(/common/images/title-{$blockConfig['id']}.png);"></div>
          {$blockConfig['title']}
          <div class="nextlink"></div>
        </a>
        <div id="{$blockConfig['elementId']}" class="blockcontent"><div class="loading"></div></div>
      </div>
    </div> 
  {/foreach}
</div>

{include file="findInclude:common/templates/footer.tpl"}
