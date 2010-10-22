{capture name="banner" assign="banner"}
  {block name="banner"}
    <h1{if isset($topItem)} class="roomfornew"{/if}>
      <img src="/modules/home/images/logo-home.png" width="265" height="45" alt="{$SITE_NAME}" />
    </h1>
  
    {if isset($topItem)}
        <div id="new"><a href="/about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
    {/if}
  {/block}
{/capture}

{include file="findInclude:common/header.tpl" customHeader=$banner scalable=false}

{block name="homeSearch"}
  {include file="findInclude:common/search.tpl" placeholder="Search "|cat:$SITE_NAME}  
{/block}

{if $home['springboard']}
  {include file="findInclude:common/springboard.tpl" springboardItems=$modules springboardID="homegrid"}
{else}
  {include file="findInclude:common/navlist.tpl" navlistItems=$modules}
{/if}


{block name="homeFooter"}
{/block}

{include file="findInclude:common/footer.tpl"}
