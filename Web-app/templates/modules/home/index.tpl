{capture name="banner" assign="banner"}
  {block name="banner"}
    <h1{if isset($topItem)} class="roomfornew"{/if}>
      <img src="/modules/home/images/harvard-logo-home.png" width="265" height="45" alt="{$SITE_NAME}" />
    </h1>
  
    {if isset($topItem)}
        <div id="new"><a href="/about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
    {/if}
  {/block}
{/capture}

{include file="findInclude:common/header.tpl" customHeader=$banner}

{include file="findInclude:common/search.tpl" placeholder="Search "|cat:$SITE_NAME}  
  
{block name="modulesStart"}
  <div id="homegrid">
{/block}

    {strip}
      {foreach $homeModules as $category => $categoryModules}
      
        {block name="categoryStartTag"}
        {/block}
        
          {foreach $categoryModules as $id => $info}
            {block name="module"}
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
            {/block}
          {/foreach}
        
        {block name="categoryEndTag"}
          {if !$categoryModules@last}
            <div class="separator">&nbsp;</div>
          {/if}
        {/block}
        
      {/foreach}
    {/strip}

{block name="modulesEnd"}
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}
