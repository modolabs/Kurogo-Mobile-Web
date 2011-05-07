{capture name="banner" assign="banner"}

  <h1 id="homelogo"{if isset($topItem)} class="roomfornew"{/if}>
    <img src="/modules/{$moduleID}/images/logo-home{$imageExt}" width="{$banner_width|default:265}" height="{$banner_height|default:45}" alt="{$strings.SITE_NAME|escape}" />
  </h1>
  {block name="topItem"} 
    {if isset($topItem)}
      <div id="new"><a href="/about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
    {/if}
  {/block}
{/capture}

{include file="findInclude:common/templates/header.tpl" customHeader=$banner scalable=false}

{if $showFederatedSearch}
{block name="federatedSearch"}
{include file="findInclude:common/templates/search.tpl" placeholder="Search "|cat:$strings.SITE_NAME}
{/block}
{/if}

{if $displayType == 'springboard'}
  {include file="findInclude:common/templates/springboard.tpl" springboardItems=$modules springboardID="homegrid"}
  
{elseif $displayType == 'list'}
  {$primaryModules = array()}
  {$secondaryModules = array()}
  {$foundSeparator = false}
  {foreach $modules as $module}
    {if $module['separator']}
      {$foundSeparator = true}
    {elseif $foundSeparator}
      {$secondaryModules[] = $module}
    {else}
      {$primaryModules[] = $module}
    {/if}
  {/foreach}

  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$primaryModules}
  {if $secondaryModules}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$secondaryModules accessKeyLink=false}
  {/if}
{/if}

{block name="homeFooter"}
{if $SHOW_DOWNLOAD_TEXT}
  <div id="download">
    <a href="/download/">
      <img src="/modules/{$moduleID}/images/download.png"
      alt="Download" align="absmiddle" />
      {$SHOW_DOWNLOAD_TEXT}
    </a>
    <br />
  </div>
{/if}  
{/block}

{include file="findInclude:common/templates/footer.tpl"}
