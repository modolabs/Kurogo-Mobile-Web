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

{include file="findInclude:common/header.tpl" customHeader=$banner}

{block name="homeSearch"}
  {include file="findInclude:common/search.tpl" placeholder="Search "|cat:$SITE_NAME}  
{/block}

{strip}

{block name="modulesList"}
  {if $home['springboard'] || $gridOnly}
    <div id="homegrid">
      {foreach $homeModules as $category => $categoryModules}
        {foreach $categoryModules as $id => $info}
          <div id="{$id}"{if $category == 'secondary'} class="utility"{/if}>
            <a href="{$info['url']}">
              <img src="{$info['img']}" alt="{$info['title']}" />
              <br/>{$info['title']}
              {if isset($info['subTitle'])}
                <br/><span class="fineprint">{$info['subTitle']}</span>
              {/if}
              {if $id == 'about' && $whatsNewCount > 0}
                <span class="badge">{$whatsNewCount}</span>
              {/if}
            </a>
          </div>
        {/foreach}
        {if !$categoryModules@last}
          <div class="separator">&nbsp;</div>
        {/if}
      {/foreach}
    </div>
  {else}
    <ul class="nav" id="homelist">
      {foreach $homeModules as $category => $categoryModules}
        {foreach $categoryModules as $id => $info}
          <li{if $category == 'secondary'} class="utility"{/if}>
            <a href="{$info['url']}">
              {if $id == 'about' && $whatsNewCount > 0}
                <span class="badge">{$whatsNewCount}</span>
              {/if}
              <img src="{$info['img']}" alt="{$info['title']}" width="50" height="50"/>
              &nbsp;{$info['title']}
              {if isset($info['subTitle'])}
                <br/><span class="fineprint">{$info['subTitle']}</span>
              {/if}
            </a>
          </li>
        {/foreach}
      {/foreach}
    </ul>
  {/if}
{/block}

{/strip}

{block name="homeFooter"}
{/block}

{include file="findInclude:common/footer.tpl"}
